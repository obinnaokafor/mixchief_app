<?php

namespace App\Controller;

use App\Controller\Controller;
// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Item;
use App\Entity\Groups;
use App\Entity\Orders;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\OrderDelivery;
use App\Entity\OrderItem;
use App\Entity\Subscription;
use App\Entity\Users;
use App\Service\PaystackHelper;
use App\Service\RegisterActivity;
use App\Service\SESEmailClient;

class DefaultController extends Controller
{

    private $ra;
    private $ses;
    private $paystackHelper;

    function __construct(RegisterActivity $ra, SESEmailClient $ses, PaystackHelper $paystackHelper)
    {
        $this->ra = $ra;
        $this->ses = $ses;
        $this->paystackHelper = $paystackHelper;
    }

    /**
     * @Route("/", name="homepage")
     **/
    public function indexAction(Request $request)
    {
        // return $this->redirectToRoute('app_login');
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(Users::class)->findOneBy(['email' => 'hello@themixchief.com']);
        $items = $em->getRepository(Item::class)->findBy(['userId' => $user->getId()]);
        $categories = $em->getRepository(Groups::class)->findBy(['userId' => $user->getId()]);
        return $this->render('Default/index.html.twig', ['items' => $items, 'categories' => $categories]);
    }

    /**
     * @Route("/checkout/{error}", name="checkout")
     */
    public function checkout(Request $request, $error = null)
    {
        // $em = $this->getDoctrine()->getManager();
        // $user = $this->getUser()->getId();
        // $items = $em->getRepository(Item::class)->findBy(['userId' => $user]);
        // $categories = $em->getRepository(Groups::class)->findBy(['userId' => $user]);
        return $this->render('Default/checkout.html.twig', ['error' => $error]);
    }

    private function handleArrayInput(array $arrayInput)
    {
        try {
            $keys = array_keys($arrayInput);
            $f = array();
            for ($i=0; $i < count($arrayInput[$keys[0]]); $i++) {
                $n = array();
                foreach ($keys as $key) {
                    $n[$key] = $arrayInput[$key][$i];
                }
                $f[] = $n;
            }

            return $f;
        } catch (\Exception $e) {

        }
    }

    private function getOrCreateCustomer($data, $user_id)
    {
        $em = $this->getDoctrine()->getManager();
        $customer = $em->getRepository(Customer::class)->findOneBy(['email' => $data['email'], 'userId' => $user_id]);
        if (!$customer) {
            $customer = new Customer();
            $customer->setName($data['name']);
            $customer->setEmail($data['email']);
            $customer->setTelephone($data['phone']);
            $customer->setAddress($data['address']);
            $customer->setUserId($user_id);
            $em->persist($customer);
            $em->flush();
        }

        return $customer;
    }

    /**
     * @Route("/order/checkout", name="postCheckout", methods={"POST"})
     */
    public function postCheckout(Request $request)
    {
        // new order
        if (!$this->isCsrfTokenValid('checkout', $request->request->get('token'))) {
            return $this->redirectToRoute('homepage');
        }

        $post = $request->request->all();
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(Users::class)->findOneBy(['email' => 'hello@themixchief.com']);
        $order = new Orders();
        $order->setUserId($user->getId());
        $order->setDate(new \DateTime('now'));
        $order->setStatus('pending');
        $order->setPayment('cash');
        // $order->setCustomer($post['email']);
        $order->setPhone($post['phone']);

        // check if customer exists
        $customer = $this->getOrCreateCustomer($post, $user->getId());
        
        // remove array objects
        unset($post['email'], $post['phone'], $post['token'], $post['address'], $post['landmark'], $post['name']);

        $orderItems = $this->handleArrayInput($post);
        $items = [];
        $orderTotal = 0;

        foreach ($orderItems as $orderItem) {
            $item = $em->getRepository(Item::class)->find($orderItem['item']);
            $it = [];
            $it['item_id'] = $item->getId();
            $it['quantity'] = $orderItem['quantity'];
            $it['price'] = $item->getSellingPrice();
            $orderTotal += $item->getSellingPrice() * $orderItem['quantity'];
            $items[] = $it;
        }
        
        $order->setAmount($orderTotal);
        $order->setCustomer($customer);

        $orderPayment = $this->paystackHelper->orderPayment($order);
        if ($orderPayment) {
            $order->setPaymentReference($orderPayment['reference']);
            $em->persist($order);
            $em->flush();
            // save delivery information
            $delivery = new OrderDelivery();
            $delivery->setOrders($order);
            $delivery->setAddress($customer->getAddress());
            // $delivery->setLandmark($post['landmark']);
            $delivery->setFullname($customer->getName());
            $delivery->setPhone($customer->getTelephone());
            $delivery->setEmail($customer->getEmail());
            $delivery->setStatus('pending');
            $delivery->setUserId($user->getId());
            $em->persist($delivery);

            foreach ($items as $item) {
                $orderItem = new OrderItem();
                $orderItem->setItemId($item['item_id']);
                $orderItem->setOrderId($order->getId());
                $orderItem->setQuantity($item['quantity']);
                $orderItem->setPrice($item['price']);
                $orderItem->setUserId($user->getId());
                $em->persist($orderItem);
            }
            $em->flush();
            return $this->redirect($orderPayment['url']);
        } else {
            return $this->redirectToRoute('checkout', ['error' => 'Error occured while processing payment']);
        }
    }

    /**
     * @Route("/order/payment/callback", name="paymentCallback")
     */
    public function paymentCallback(Request $request)
    {
        $reference = $request->get('reference');
        $em = $this->getDoctrine()->getManager();
        $order = $em->getRepository(Orders::class)->findOneBy(['payment_reference' => $reference]);
        if ($order->getConfirmed() || !$order) {
            return $this->redirectToRoute('homepage');
        }

        $res = $this->paystackHelper->verifyPayment($reference);
        
        if (!$res) {
            return $this->redirectToRoute('checkout', ['error' => 'An error occured while verifying payment']);
        } else {
            $order->setStatus('paid');
            $em->persist($order);
            $em->flush();
        }
        // $em = $this->getDoctrine()->getManager();
        return $this->redirectToRoute('confirmation', ['reference' => $reference]);
    }

    /**
     * @Route("/order/confirmation/{reference}", name="confirmation")
     *
     **/
    public function confirmation($reference)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(Users::class)->findOneBy(['email' => 'hello@themixchief.com']);
        $order = $em->getRepository(Orders::class)->findOneBy(['payment_reference' => $reference, 'userId' => $user->getId()]);
        if (!$order || $order->getConfirmed()) {
            return $this->redirectToRoute('homepage');
        }
        $orderItems = $em->getRepository(OrderItem::class)->findOrderItems($order->getId(), $user->getId());
        // var_dump($orderItems);die;
        $delivery = $em->getRepository(OrderDelivery::class)->findOneBy(['orders' => $order->getId(), 'user_id' => $user->getId()]);

        $message = $this->renderView(
            "Users/receipt.html.twig",
            [
                'delivery' => $delivery,
                'items' => $orderItems,
                'order' => $order
            ]
        );

        $this->ses->sendEmail('hello@themixchief.com', 'Receipt for order', $message);
        $this->ses->sendEmail($delivery->getEmail(), 'Receipt for order', $message);
        $order->setConfirmed(true);
        $em->persist($order);
        $em->flush();

        return $this->render('Default/orderconfirmation.html.twig', ['order' => $order, 'orderItems' => $orderItems, 'delivery' => $delivery]);
    }

    /**
     * @Route("/order/payment/error", name="error_page")
     **/
    public function errorPage($error)
    {
        return $this->render('Default/error.html.twig', ['error' => $error]);
    }

    /**
     * @Route("/howto", name="howto")
     **/
    public function howTo()
    {
        return $this->render('Default/howto.html.twig');
    }

    /**
     * @Route("/positems", name="positems")
     */
    public function posItemsAction(Request $request)
    {
        $user = $this->getUser()->getId();
        $products = $this->getDoctrine()
                        ->getRepository(Item::class)->findPointOfSale($user);
        $response = new JsonResponse();
        $response->setData($products);
        return $response;
    }

    /**
     * @Route("/admin", name="admin")
     */
    public function adminAction(Request $request)
    {
        // return new Response('Hello there');
        // $products = "Mike";
        $user = $this->getUser();
        $date = date('d-m-Y');
        $tod = new \DateTime($date);
        $yes = new \DateTime($date);
        $yes->modify("-1 day");
        $now = new \DateTime('now');
        $prev = new \DateTime('now');
        $em = $this->getDoctrine()->getManager();
        $subscription = $em->getRepository(Subscription::class)->findOneBy(['user' => $user]);

        // Get today's and yesterday's total
        $today = $em->getRepository(Orders::class)->salesTotal($user->getId(), $tod, $now);
        $yesterday = $em->getRepository(Orders::class)->salesTotal($user->getId(), $yes, $tod);

        $dow = date('w');
        $tweek = date('d-m-Y', strtotime('-'.$dow.' days'));
        $lweek = date('d-m-Y', strtotime('-'.$dow.' days'));
        $bow = new \DateTime($tweek);
        $bolw = new \DateTime($lweek);
        $bolw->modify('-7 days');

        $m = date('01-m-Y');
        $lm = date('01-m-Y');
        $bom = new \DateTime($m);
        $bolm = new \DateTime($m);
        $bolm->modify('-1 month');

        $thisWeekTotal = $em->getRepository(Orders::class)->salesTotal($user->getId(), $bow, $now);
        $lastWeekTotal = $em->getRepository(Orders::class)->salesTotal($user->getId(), $bolw, $bow);
        $thisMonthTotal = $em->getRepository(Orders::class)->salesTotal($user->getId(), $bom, $now);
        $lastMonthTotal = $em->getRepository(Orders::class)->salesTotal($user->getId(), $bolm, $bom);
        $salesmonth = $em->getRepository(Orders::class)->salesPeriod($user->getId(), date('Y-m-01 H:i:s'), $now->format('Y-m-d H:i:s'));

        $denominator = $yesterday ?: 1;
        $increase = $today ? (($today - $yesterday)/$denominator)*100 : 0;
        $activities = $this->getDoctrine()->getRepository(Activity::class)->findBy(['userId' => $user->getId()], ['date' => 'DESC'], 15);
        $diff = '';
        $stat = '';
        
        // $diff = $prev >= $user->getCreated();

        // if user doesn't have a running subscription (cancelled or never subscribed)
        if (!$subscription || !$subscription->getStatus()) {
            $prev->modify('-14 days');
            $diff = $prev >= $user->getCreated();
            $stat = $diff ? 'Expired' : $prev->diff($user->getCreated())->format('%a days left');
        }

        return $this->render('Default/admin.html.twig', ['title' => 'Dashboard', 'activities' => $activities, 'diff' => $diff, 'stat' => $stat, 'subscription' => $subscription, 'today' => $today, 'yesterday' => $yesterday, 'increase' => floor($increase), 'thisweek' => $thisWeekTotal, 'lastweek' => $lastWeekTotal, 'thismonth' => $thisMonthTotal, 'lastmonth' => $lastMonthTotal, 'salesmonth' => json_encode($salesmonth)]);
        // return $this->render('Default/index.html.twig', array('title' => 'EPOS', 'product' => $products));
    }

    /**
     * Get all items from database and push to template
     * @Route("/all", name="get_all")
     * @return Response
     */
    public function allAction()
    {
      $user = $this->getUser();
      $cats = $this->getDoctrine()->getRepository(Groups::class)->findAll();
      $items = $this->getDoctrine()->getRepository(Item::class)->findAll();

      $response = new JsonResponse();
      $response->setData($cats);

      return $response;
    }

    /**
     * @Route("/drinks", name="drinks")
     */
    public function drinksAction(Request $request)
    {
        $user = $this->getUser();
        // $products = "Mike";
        $products = $this->getDoctrine()
                        ->getRepository(Item::class)->findPointOfSaleDrinks();
        $response = new JsonResponse();
        $response->setData(json_encode($products));

        // // set a HTTP response header
        $response->headers->set('Content-Type', 'application/json');
        return $response;
        // return $this->render('Default/index.html.twig', array('title' => 'EPOS', 'product' => $products));
    }

    /**
     * @Route("/pos", name="pos")
     */
    public function posAction(Request $request)
    {
        $user = $this->getUser()->getId();
        $lastOrder = $this->getDoctrine()
                        ->getRepository(Orders::class)->findLastOrder($user);
        return $this->render('POS/index.html.twig', array('title' => 'EPOS'));
    }

    /**
     * @Route("/pos/drinks", name="posDrinks")
     */
    public function posDrinksAction()
    {
        $waiter = $this->getDoctrine()
                        ->getRepository(Staff::class)->findBy(array('role' => 'Waiter'));
        $table = $this->getDoctrine()
                        ->getRepository(Tables::class)->findAll();
        $lastOrder = $this->getDoctrine()
                        ->getRepository(Orders::class)->findLastOrder();
        return $this->render('POS/index.html.twig', array('title' => 'EPOS', 'waiter' => $waiter, 'table' => $table));
    }
}
