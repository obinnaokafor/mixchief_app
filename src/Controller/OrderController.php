<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\MailgunTransport;
use App\Service\RegisterActivity;
use App\Entity\Item;
use App\Entity\Orders;
use App\Entity\OrderItem;
use App\Entity\Cancelled;
use App\Entity\ItemPart;
use App\Entity\Stock;
use App\Entity\Customer;

class OrderController extends Controller
{
    private $ra;
    private $mailgun;

    function __construct(RegisterActivity $ra, MailgunTransport $mailgun)
    {
        $this->ra = $ra;
        $this->mailgun = $mailgun;
    }

    /**
     * @Route("/admin/order", name="order", methods="POST")
     */
    public function orderPutAction(Request $request)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $data = json_decode($request->getContent(), true);
        $date = new \DateTime('now');
        $order = $data['id'] ? $em->getRepository(Orders::class)->findOneBy(['id' => $data['id'], 'userId' => $user]) : new Orders();
        $order->setStatus($data['status']);
        $order->setAmount($data['total']);
        if ($set = isset($data['customer']) && $email = $data['customer']) {
            // if (!$customer = $em->getRepository(Orders::class)->findOneBy(['customer' => $data['customer']])) {
            //     $customer = new Customer();
            //     $customer->setEmail($data['customer']);
            //     $em->persist($customer);
            //     $em->flush();
            // }
            
            $order->setCustomer($email);
        }
        $order->setUserId($user);
        $order->setPayment(isset($data['payment']) ? $data['payment'] : 'Cash');
        if ($data['id']) {
            $order->setModified($date);
            if ($data['status'] === 'Completed') {
                $message = 'Sale completed at ' . $date->format('d/m/Y');
            }else {
                $message = 'Sale modified at ' . $date->format('d/m/Y');
            }
            
        }else {
            $order->setDate($date);
            $message = 'Sale of ' . $order->getAmount() . ' ' . strtolower($order->getStatus()) . ', placed at ' . $date->format('d/m/Y h:i:s A.');
        }
        $em->persist($order);
        $em->flush();

        
        $this->orderItemAction($order->getId(), $data['items'], $data['status']);

        if ($set && $email) {
            $this->eReceipt($order, $data['customer']);
        }

        $this->ra->register('Orders', $order->getId(), $message);

        return $this->json(['message' => 'Sale completed successfully', 'order' => $order]);
    }

    /**
     * @Route("/admin/orders/sales", methods="POST")
     * Get Total Sales within a timeframe
     *
     **/
    public function getTotalSalesBetween()
    {
        $from = $_POST['from'];
        $to = $_POST['to'];
        $user = $this->user();
        $total = $this->getDoctrine()->getRepository(Orders::class)->salesTotal($user->getId(), $from, $to);

        return $this->json(['total' => $total]);
    }

    /**
     * @Route("/admin/orders/sales", methods="POST")
     * Get Daily Sales within a timeframe
     *
     **/
    public function getSalesPeriod()
    {
        $from = $_POST['from'];
        $to = $_POST['to'];
        $user = $this->user();
        $total = $this->getDoctrine()->getRepository(Orders::class)->salesPeriod($user->getId(), $from, $to);

        return $this->json(['total' => $total]);
    }

    /**
     * @Route("/receipt")
     * Send e-receipt to customer
     *
     * @return void
     * @author 
     **/
    public function eReceipt($order, $email)
    {
        $user = $this->getUser();
        $items = $this->getDoctrine()->getRepository(OrderItem::class)->findOrder($order->getId(), $user->getId());
        // $order = $this->getDoctrine()->getRepository(Orders::class)->findOneBy(['id' => 78, 'userId' => $user->getId()]);
        $message = $this->renderView(
            "Users/receipt.html.twig",
            [
                'items' => $items,
                'order' => $order
            ]
        );

        // return new Response($message);

        $response = $this->mailgun->send($email, 'Receipt for order', $message, null, $user->getCompany());

        return new Response($message);
    }

    /**
     * @Route("/admin/orders/saved", name="savedorders", methods="GET")
     */
    public function savedAction()
    {
        $user = $this->getUser()->getId();
        $saved = $this->getDoctrine()->getRepository(Orders::class)->findBy(['status' => 'pending', 'userId' => $user]);
        return $this->json($saved);
    }

    public function orderItemAction($id, $items, $status = false)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        foreach ($items as $key => $value) {
            if (isset($value['status']) && ($value['status'] === 'Pending')) {
                $orderitem = $em->getRepository(OrderItem::class)->findOneBy(['orderId' =>$id, 'itemId' => $value['id'], 'userId' => $user]);
                // continue;
            }else {
                $orderitem = new OrderItem();
                $orderitem->setOrderId($id);
                $orderitem->setItemId($value['id']);
                $orderitem->setUserId($user);
            }

            $orderitem->setQuantity(intval($value['quantity']));
            $price = floatval($value['price'])*intval($value['quantity']);
            $orderitem->setPrice(floatval($value['price']));
            // if (floatval($value['discount'])>0) {
            //     $discount = (floatval($value['price']) * floatval($value['discount']))/100;
            //     $discount *= intval($value['quantity']);
            //     $orderitem->setDiscount($discount);
            // }else {
            //     $orderitem->setDiscount(floatval($value['discount']));
            // }
            
            // $em = $this->getDoctrine()->getManager();
            $em->persist($orderitem);
        }

        if ($status === 'Completed') {
            $this->deductStock($items, $em);
        }

        $em->flush();
    }

    /**
     * Deduct order items from inventory
     *
     * @return Bool
     **/
    public function deductStock($items, $em)
    {
        $user = $this->getUser()->getId();
        foreach ($items as $item) {
            $itempart = $this->getDoctrine()->getRepository(ItemPart::class)->findByItem($item['id'], $user);
            for ($i=0; $i < sizeof($itempart); $i++) {
                $stock = $this->getDoctrine()->getRepository(Stock::class)->findOneBy(['id' => $itempart[$i]->getStockId(), 'userId' => $user]);
                $stock->setQuantity(floatval($stock->getQuantity()) - (floatval($itempart[$i]->getPortion())*$item['quantity']));
                $em->persist($stock);
            }
        }
    }

    /**
     * @Route("/admin/order/delete/{id}", name="deleteorder")
     *
     * @param int $id Order ID
     **/
    public function deleteOrder($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getEntityManager();
        $order = $em->getRepository(Orders::class)->findOneBy(['id' => $id, 'userId' => $user]);

        $response = $this->forward('App\Controller\InventoryController::delete', [
            'query'  => 'Order',
            'id' => $id,
        ]);

        if ($response) {
            $em->remove($order);
            $em->flush();
        }

        return $this->redirectToRoute('orders');
    }

    /**
     * @Route("/order/change", name="changeorder")
     */
    public function deleteItemAction()
    {
        $user = $this->getUser()->getId();
        $response = new Response();
        if ($_POST['id']) {
            $em = $this->getDoctrine()->getEntityManager();
            $order = $this->getDoctrine()
                    ->getRepository(Orders::class)->findOneBy(['id' => $_POST['orderid'], 'userId' => $user]);
            $order->setAmount($order->getAmount() - floatval($_POST['price']));
            $item = $this->getDoctrine()->getRepository(OrderItem::class)->findOneBy(['id' => $_POST['orderitemid'], 'userId' => $user]);
            // $date = date('m/d/Y H:i:s');
            // $cancelled = new Cancelled();
            // $cancelled->setItemId($_POST['id']);
            // $cancelled->setOrderId($_POST['orderid']);
            // $cancelled->setQuantity($_POST['quantity']);
            // $cancelled->setPrice($_POST['price']);
            // $cancelled->setDate(new \DateTime($date));
            $em->persist($order);
            // $em->persist($cancelled);
            $em->remove($item);
            $em->flush();
            $response->setContent('done');
        }else {
            $response->setContent('Not Done');
        }
        return $response;
    }

    /**
     * @Route("/admin/orderitems/delete/{order}/{item}", name="deletesavedorderitems")
     */
    public function deleteOrderItem($order = null, $item = null)
    {
        if (!$item || !$order) {
            return $this->json(['message' => 'Not Found']);
        }

        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getEntityManager();
        $item = $em->getRepository(OrderItem::class)->findOneBy(['orderId' => $order, 'itemId' => $item, 'userId' => $user]);
        // $this->getDoctrine()->getRepository(OrderItem::class)->deleteOrderItem($order, $item, $user);
        if ($item) {
            $order = $em->getRepository(Orders::class)->findOneBy(['id' => $order, 'userId' => $user]);
            $order->setAmount($order->getAmount() - ($item->getPrice()*$item->getQuantity()));
            $em->remove($item);
            $em->persist($order);
            $em->flush();

            return $this->json(['success' => true]);
        }

        return $this->json(['message' => 'Item not found']);
    }

    public function completeOrderAction($id)
    {
        $user = $this->getUser()->getId();
        $order = $this->getDoctrine()
                        ->getRepository(Orders::class)->find(intval($id));
        $order->setStatus('completed');
        $order->setPayment($_POST['payment']);
        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();
    }

    /**
     * @Route("/admin/orderitems/saved", name="savedorderitems")
     */
    public function retrieveSavedOrderItemsAction(Request $request)
    {
        $user = $this->getUser()->getId();
        $savedorderitems = $this->getDoctrine()
                    ->getRepository(Orders::class)->findPendingOrders($user);
        return $this->json($savedorderitems);
    }

    /**
     * @Route("/admin/order/saved/{id}", name="savedorder", methods="GET")
     */
    public function retrieveSavedOrderAction($id = null)
    {
        if (!$id) {
            return $this->json(['message' => 'Not Found']);
        }

        $user = $this->getUser()->getId();
        $savedorder = $this->getDoctrine()
                    ->getRepository(Orders::class)->findPendingOrder($id, $user);
        return $this->json($savedorder);
    }

    /**
     * @Route("/admin/order/saved/{id}", name="deleteSavedOrder", methods="DELETE")
     */
    public function removeOrderAction($id = null)
    {
        if (!$id) {
            return $this->json(['message' => 'Not Found']);
        }

        $user = $this->getUser()->getId();

        $em = $this->getDoctrine()->getManager();
        $order = $em->getRepository(Orders::class)->findOneBy(['id' => $id, 'userId' => $user]);

        $deleted = $this->getDoctrine()
                    ->getRepository(Orders::class)->deletePendingOrder($id, $user);
        $deletedItems = $this->getDoctrine()
                    ->getRepository(OrderItem::class)->deleteOrderItems($id, $user);

        $message = 'Saved order of ' . $order->getAmount() . ', placed at ' . $order->getDate()->format('d/m/Y') . ' deleted';
        $this->ra->register('Orders', $id, $message);
        
        $response = new JsonResponse();
        $response->setData(json_encode($deleted));
        // // set a HTTP response header
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    // /**
    //  * @Route("/admin", name="admin")
    //  */
    // public function adminAction(Request $request)
    // {
    //     $user = $this->getUser()->getId();
    //     $orders = $this->getDoctrine()->getRepository(Orders::class)->findBy(['userId' => $user]);
        
    //     return $this->render('Orders/index.html.twig', array('title' => 'Orders', 'orders' => $orders));
    // }

    /**
     * @Route("/admin/allorders", name="orders")
     */
    public function allOrdersAction(Request $request)
    {
        $user = $this->getUser()->getId();
        $orders = $this->getDoctrine()->getRepository(Orders::class)->findBy(['userId' => $user], ['date' => 'DESC']);
        
        return $this->render('Orders/orders.html.twig', array('title' => 'Order', 'orders' => $orders));
    }

    /**
     * @Route("/admin/vieworder/{id}", name="vieworder")
     */
    public function viewOrderAction($id)
    {
        $user = $this->getUser()->getId();
        $order = $this->getDoctrine()->getRepository(Orders::class)->findOneBy(['id' => $id, 'userId' => $user]);
        // var_dump($order);die;
        $order_items = $this->getDoctrine()->getRepository(OrderItem::class)->findOrder($id, $user);
        
        return $this->render('Orders/order.html.twig', array('title' => 'Order', 'order' => $order, 'orderitems' => $order_items));
    }

    /**
     * @Route("/order/payments", name="payments")
     */
    public function paymentsAction()
    {
        $user = $this->getUser()->getId();
        $date = date('Y-m-d');
        $order = $this->getDoctrine()->getRepository(Orders::class)->findPayment($date, $user);
        
        $response = new JsonResponse();
        $response->setData(json_encode($order));

        // // set a HTTP response header
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/order/printreport", name="printDailyReport")
     */
    public function printReportAction()
    {
        $user = $this->getUser()->getId();
        $date = date('Y-m-d');
        $report = $this->getDoctrine()->getRepository(Orders::class)->printReport($date);
        $cancelled = $this->getDoctrine()->getRepository(Orders::class)->findCancelled($date);
        $order = $this->getDoctrine()->getRepository(Orders::class)->findPayment($date);
        $count = sizeof($report);
        // for ($i=0; $i < sizeof($cancelled); $i++) { 
        //     $report[$count] = $cancelled[$i];
        //     $count++;
        // }

        $combined = array('report' => json_encode($report), 'cancelled' => json_encode($cancelled), 'payments' => json_encode($order));
        $response = new JsonResponse();
        $response->setData(json_encode($combined));

        // // set a HTTP response header
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}