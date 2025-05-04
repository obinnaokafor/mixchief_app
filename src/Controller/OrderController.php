<?php

namespace App\Controller;

use App\Entity\Cancelled;
use App\Entity\Customer;
use App\Entity\Item;
use App\Entity\ItemPart;
use App\Entity\OrderItem;
use App\Entity\Orders;
use App\Entity\Stock;
use App\Service\MailgunTransport;
use App\Service\RegisterActivity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends Controller
{
    private $ra;
    private $mailgun;

    public function __construct(RegisterActivity $ra, MailgunTransport $mailgun)
    {
        $this->ra = $ra;
        $this->mailgun = $mailgun;
    }

    #[Route("/admin/order", name: "order", methods: ["POST"])]
    public function orderPut(Request $request): JsonResponse
    {
        $user = $this->getUser()->getId();
        $data = json_decode($request->getContent(), true);
        $date = new \DateTime('now');
        $order = $data['id'] ? $this->em->getRepository(Orders::class)->findOneBy(['id' => $data['id'], 'userId' => $user]) : new Orders();
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
                $message = 'Sale completed at '.$date->format('d/m/Y');
            } else {
                $message = 'Sale modified at '.$date->format('d/m/Y');
            }
        } else {
            $order->setDate($date);
            $message = 'Sale of '.$order->getAmount().' '.strtolower($order->getStatus()).', placed at '.$date->format('d/m/Y h:i:s A.');
        }
        $this->em->persist($order);
        $this->em->flush();

        $this->orderItemAction($order->getId(), $data['items'], $data['status']);

        if ($set && $email) {
            $this->eReceipt($order, $data['customer']);
        }

        $this->ra->register('Orders', $order->getId(), $message);

        return $this->json(['message' => 'Sale completed successfully', 'order' => $order]);
    }

    #[Route("/admin/order/total", name: "order_total")]
    public function getTotalSalesBetween(): JsonResponse
    {
        $from = $_POST['from'];
        $to = $_POST['to'];
        $user = $this->user();
        $total = $this->em->getRepository(Orders::class)->salesTotal($user->getId(), $from, $to);

        return $this->json(['total' => $total]);
    }

    #[Route("/admin/order/sales", name: "order_sales")]
    public function getSalesPeriod(): JsonResponse
    {
        $from = $_POST['from'];
        $to = $_POST['to'];
        $user = $this->user();
        $total = $this->em->getRepository(Orders::class)->salesPeriod($user->getId(), $from, $to);

        return $this->json(['total' => $total]);
    }

    #[Route("/admin/order/receipt/{order}/{email}", name: "order_receipt")]
    public function eReceipt($order, $email): Response
    {
        $user = $this->getUser();
        $items = $this->em->getRepository(OrderItem::class)->findOrder($order->getId(), $user->getId());
        // $order = $this->getDoctrine()->getRepository(Orders::class)->findOneBy(['id' => 78, 'userId' => $user->getId()]);
        $message = $this->renderView(
            'Users/receipt.html.twig',
            [
                'items' => $items,
                'order' => $order,
            ]
        );

        // return new Response($message);

        $response = $this->mailgun->send($email, 'Receipt for order', $message, null, $user->getCompany());

        return new Response($message);
    }

    #[Route("/admin/order/saved", name: "order_saved")]
    public function saved(): JsonResponse
    {
        $user = $this->getUser()->getId();
        $saved = $this->em->getRepository(Orders::class)->findBy(['status' => 'pending', 'userId' => $user]);

        return $this->json($saved);
    }

    #[Route("/admin/order/item/{id}/{items}/{status}", name: "order_item")]
    public function orderItem($id, $items, $status = false)
    {
        $user = $this->getUser()->getId();
        foreach ($items as $value) {
            if (isset($value['status']) && ($value['status'] === 'Pending')) {
                $orderitem = $this->em->getRepository(OrderItem::class)->findOneBy(['orderId' => $id, 'itemId' => $value['id'], 'userId' => $user]);
            // continue;
            } else {
                $orderitem = new OrderItem();
                $orderitem->setOrderId($id);
                $orderitem->setItemId($value['id']);
                $orderitem->setUserId($user);
            }

            $orderitem->setQuantity(intval($value['quantity']));
            $price = floatval($value['price']) * intval($value['quantity']);
            $orderitem->setPrice(floatval($value['price']));
            // if (floatval($value['discount'])>0) {
            //     $discount = (floatval($value['price']) * floatval($value['discount']))/100;
            //     $discount *= intval($value['quantity']);
            //     $orderitem->setDiscount($discount);
            // }else {
            //     $orderitem->setDiscount(floatval($value['discount']));
            // }

            // $em = $this->getDoctrine()->getManager();
            $this->em->persist($orderitem);
        }

        if ($status === 'Completed') {
            $this->deductStock($items);
        }

        $this->em->flush();
    }

    /**
     * Deduct order items from inventory.
     *
     * @return bool
     **/
    public function deductStock($items, $em)
    {
        $user = $this->getUser()->getId();
        foreach ($items as $item) {
            $itempart = $this->em->getRepository(ItemPart::class)->findByItem($item['id'], $user);
            for ($i = 0; $i < count($itempart); ++$i) {
                $stock = $this->em->getRepository(Stock::class)->findOneBy(['id' => $itempart[$i]->getStockId(), 'userId' => $user]);
                $stock->setQuantity(floatval($stock->getQuantity()) - (floatval($itempart[$i]->getPortion()) * $item['quantity']));
                $this->em->persist($stock);
            }
        }
    }

    #[Route("/admin/order/delete/{id}", name: "order_delete")]
    public function deleteOrder($id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $user = $this->getUser()->getId();
        $order = $this->em->getRepository(Orders::class)->findOneBy(['id' => $id, 'userId' => $user]);

        $response = $this->forward('App\Controller\InventoryController::delete', [
            'query' => 'Order',
            'id' => $id,
        ]);

        if ($response) {
            $this->em->remove($order);
            $this->em->flush();
        }

        return $this->redirectToRoute('orders');
    }

    #[Route("/admin/order/item/delete", name: "order_item_delete")]
    public function deleteItem(): Response
    {
        $user = $this->getUser()->getId();
        $response = new Response();
        if ($_POST['id']) {
            // $em = $this->entityManager;
            $order = $this->em->getRepository(Orders::class)->findOneBy(['id' => $_POST['orderid'], 'userId' => $user]);
            $order->setAmount($order->getAmount() - floatval($_POST['price']));
            $item = $this->em->getRepository(OrderItem::class)->findOneBy(['id' => $_POST['orderitemid'], 'userId' => $user]);
            // $date = date('m/d/Y H:i:s');
            // $cancelled = new Cancelled();
            // $cancelled->setItemId($_POST['id']);
            // $cancelled->setOrderId($_POST['orderid']);
            // $cancelled->setQuantity($_POST['quantity']);
            // $cancelled->setPrice($_POST['price']);
            // $cancelled->setDate(new \DateTime($date));
            $this->em->persist($order);
            // $em->persist($cancelled);
            $this->em->remove($item);
            $this->em->flush();
            $response->setContent('done');
        } else {
            $response->setContent('Not Done');
        }

        return $response;
    }

    #[Route("/admin/order/item/delete/{order}/{item}", name: "order_item_delete_specific")]
    public function deleteOrderItem($order = null, $item = null): JsonResponse
    {
        if (!$item || !$order) {
            return $this->json(['message' => 'Not Found']);
        }

        $user = $this->getUser()->getId();
        // $em = $this->em;
        $item = $this->em->getRepository(OrderItem::class)->findOneBy(['orderId' => $order, 'itemId' => $item, 'userId' => $user]);
        // $this->getDoctrine()->getRepository(OrderItem::class)->deleteOrderItem($order, $item, $user);
        if ($item) {
            $order = $this->em->getRepository(Orders::class)->findOneBy(['id' => $order, 'userId' => $user]);
            $order->setAmount($order->getAmount() - ($item->getPrice() * $item->getQuantity()));
            $this->em->remove($item);
            $this->em->persist($order);
            $this->em->flush();

            return $this->json(['success' => true]);
        }

        return $this->json(['message' => 'Item not found']);
    }

    #[Route("/admin/order/complete/{id}", name: "order_complete")]
    public function completeOrder($id)
    {
        $user = $this->getUser()->getId();
        $order = $this->em->getRepository(Orders::class)->find(intval($id));
        $order->setStatus('completed');
        $order->setPayment($_POST['payment']);
        $this->em->persist($order);
        $this->em->flush();
    }

    #[Route("/admin/order/saved/items", name: "order_saved_items")]
    public function retrieveSavedOrderItems(): JsonResponse
    {
        $user = $this->getUser()->getId();
        $savedorderitems = $this->em->getRepository(Orders::class)->findPendingOrders($user);

        return $this->json($savedorderitems);
    }

    #[Route("/admin/order/saved/{id}", name: "order_saved_specific")]
    public function retrieveSavedOrder($id = null): JsonResponse
    {
        if (!$id) {
            return $this->json(['message' => 'Not Found']);
        }

        $user = $this->getUser()->getId();
        $savedorder = $this->em->getRepository(Orders::class)->findPendingOrder($id, $user);

        return $this->json($savedorder);
    }

    #[Route("/admin/order/remove/{id}", name: "order_remove")]
    public function removeOrder($id = null): JsonResponse
    {
        if (!$id) {
            return $this->json(['message' => 'Not Found']);
        }

        $user = $this->getUser()->getId();

        $order = $this->em->getRepository(Orders::class)->findOneBy(['id' => $id, 'userId' => $user]);

        $deleted = $this->em->getRepository(Orders::class)->deletePendingOrder($id, $user);
        $deletedItems = $this->em->getRepository(OrderItem::class)->deleteOrderItems($id, $user);

        $message = 'Saved order of '.$order->getAmount().', placed at '.$order->getDate()->format('d/m/Y').' deleted';
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

    #[Route("/admin/orders", name: "orders")]
    public function allOrders(): Response
    {
        $user = $this->getUser()->getId();
        $orders = $this->em->getRepository(Orders::class)->findBy(['userId' => $user], ['date' => 'DESC']);

        return $this->render('Orders/orders.html.twig', ['title' => 'Order', 'orders' => $orders]);
    }

    #[Route("/admin/order/view/{id}", name: "order_view")]
    public function viewOrder($id): Response
    {
        $user = $this->getUser()->getId();
        $order = $this->em->getRepository(Orders::class)->findOneBy(['id' => $id, 'userId' => $user]);
        // var_dump($order);die;
        $order_items = $this->em->getRepository(OrderItem::class)->findOrder($id, $user);

        return $this->render('Orders/order.html.twig', ['title' => 'Order', 'order' => $order, 'orderitems' => $order_items]);
    }

    #[Route("/admin/order/payments", name: "order_payments")]
    public function payments(): JsonResponse
    {
        $user = $this->getUser()->getId();
        $date = date('Y-m-d');
        $order = $this->em->getRepository(Orders::class)->findPayment($date, $user);

        $response = new JsonResponse();
        $response->setData(json_encode($order));

        // // set a HTTP response header
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    #[Route("/admin/order/report", name: "order_report")]
    public function printReport(): JsonResponse
    {
        $user = $this->getUser()->getId();
        $date = date('Y-m-d');
        $report = $this->em->getRepository(Orders::class)->printReport($date, $user);
        $cancelled = $this->em->getRepository(Orders::class)->findCancelled($date, $user);
        $order = $this->em->getRepository(Orders::class)->findPayment($date, $user);
        $count = count($report);
        // for ($i=0; $i < sizeof($cancelled); $i++) {
        //     $report[$count] = $cancelled[$i];
        //     $count++;
        // }

        $combined = ['report' => json_encode($report), 'cancelled' => json_encode($cancelled), 'payments' => json_encode($order)];
        $response = new JsonResponse();
        $response->setData(json_encode($combined));

        // // set a HTTP response header
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
