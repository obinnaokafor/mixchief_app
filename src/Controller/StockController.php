<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Item;
use App\Entity\Supply;
use App\Entity\SupplyItem;
use App\Entity\Supplier;
use App\Entity\Stock;
use App\Entity\Orders;
use App\Entity\OrderItem;
use App\Entity\Spillage;

class StockController extends AbstractController
{

    /**
     * @Route("/admin/stock", name="stock")
     */
    public function stockAction(Request $request)
    {
        $user = $this->getUser()->getId();
        $stock = $this->getDoctrine()->getRepository(Stock::class)->findBy(['userId' => $user, 'deleted' => NULL], ['name' => 'ASC']);

        $stockitem = [];
        // Get stock item to edit
        if (isset($_GET['id'])) {
            $stockitem = $this->getDoctrine()->getRepository(Stock::class)->findOneBy(['id' => $_GET['id'], 'userId' => $user]);
        }

        if (isset($_POST['name'])) {
            $em = $this->getDoctrine()->getManager();
            // check if its an update then update item with id else add new stock items
            if (isset($_POST['edit'])) {
                $stockitem = $this->getDoctrine()->getRepository(Stock::class)->findOneBy(['id' => $_POST['edit'], 'userId' => $user]);
                $stockitem->setName($_POST['name'][0]);
                $stockitem->setUnit($_POST['unit'][0]);
                $stockitem->setQuantity(isset($_POST['quantity'][0]) ? $_POST['quantity'][0] : 0);
                $em->persist($stockitem);
            }else{
                for ($i=0; $i<sizeof($_POST['name']); $i++) {
                    $stockitem = new Stock();
                    $stockitem->setName($_POST['name'][$i]);
                    $stockitem->setUserId($user);
                    $stockitem->setUnit($_POST['unit'][$i]);
                    $stockitem->setQuantity($_POST['quantity'][$i] ?: 0);
                    $em->persist($stockitem);
                }
            }
            $em->flush();
            
            return $this->redirectToRoute('stock');
        }
        
        return $this->render('Stock/index.html.twig', array('title' => 'Stock', 'stock' => $stock, 'stockitem' => $stockitem));
    }

    /**
     * @Route("/admin/inventory/sales/{id}", name="inventorysales")
     *
     * @param int $id inventory item id
     **/
    public function inventorySales($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();

        
    }

    /**
     * @Route("/ajax/{page}", name="ajax")
     *
     **/
    public function ajax($page)
    {
        return $this->render("ajax/$page.html.twig");
    }

    /**
     * @Route("/admin/supply/{supplierId}", name="supply")
     *
     * Add Supply
     *
     */
    public function supplyAction(Request $request, $supplierId = NULL)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $suppliers = $em->getRepository(Supplier::class)->findBy(['userId' => $user]);
        $items = $em->getRepository(Stock::class)->findBy(['userId' => $user, 'deleted' => NULL]);

        if (isset($_POST['supplier'])) {

            $date = date('m/d/Y H:i:s');
            $supply = new Supply();
            $supply->setSupplier($em->getRepository(Supplier::class)->findOneBy(['userId' => $user, 'id' => $_POST['supplier']]));
            $total = array_sum($_POST['amount']);
            $supply->setTotal($total);
            $supply->setUserId($user);
            $supply->setDate(\DateTime::createFromFormat('d/m/Y', $_POST['date']));
            $em = $this->getDoctrine()->getManager();
            $em->persist($supply);
            $em->flush();
            $this->supplyItemAction($supply->getId(), $_POST);
            
            return $this->redirectToRoute('supplyitems', array('id' => $supply->getId()));
        }

        return $this->render('Stock/supply.html.twig', array('title' => 'Enter Supply', 'suppliers' => $suppliers, 'items' => $items, 'supplierId' => $supplierId));
    }

    /**
     * @Route("/admin/supplies", name="supplies")
     *
     * Show all supplies
     *
     */
    public function suppliesAction()
    {
        $user = $this->getUser()->getId();
        $supplies = $this->getDoctrine()->getRepository(Supply::class)->findBy(['userId' => $user]);
        return $this->render('Stock/supplies.html.twig', array('title' => 'Supplies', 'supplies' => $supplies)); 
    }

    /**
     * @Route("/admin/supplyitems/{id}", name="supplyitems")
     *
     * Show all supply items part of a supply
     */
    public function supplyItemsAction($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $supply = $em->getRepository(Supply::class)->findOneBy(['id' => $id, 'userId' => $user]);
        $supplies = $em->getRepository(SupplyItem::class)->findSupplyItems($id, $user);

        return $this->render('Stock/itemSupplies.html.twig', array('title' => 'Supplies', 'supplies' => $supplies, 'supply' => $supply));
    }

    /**
     * @Route("/admin/supplyitems/stock/{id}", name="stocksupplyitems")
     *
     * Show all supply items of an inventory/stock item
     */
    public function supplyItemsByStock($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $stock = $em->getRepository(Stock::class)->findOneBy(['id' => $id, 'userId' => $user]);
        $supplies = $em->getRepository(SupplyItem::class)->findSupplyItemsByStock($id, $user);

        return $this->render('Stock/stocksupplies.html.twig', array('title' => 'Stock Supplies', 'supplies' => $supplies, 'stock' => $stock));
    }

    /**
     * @Route("/admin/stock/supplyitems/{id}", name="stocksupply")
     *
     * @param int $id Stock id
     **/
    public function suppliesByStock($id)
    {
        $supplies = $this->getDoctrine()->getRepository(SupplyItem::class)->findAllSupplyItem($user);
    }

    /**
     * @Route("/admin/supplier/{id}/supplies", name="suppliesbysupplier")
     *
     * Show supplies by supplier identified by id
     *
     * @param int $id Supplier ID
     **/
    public function suppliesBySupplier($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $supplier = $em->getRepository(Supplier::class)->findOneBy(['userId' => $user, 'id' => $id]);

        return $this->render('Stock/supplies.html.twig', array('title' => "Supplies", 'supplies' => $supplier->getSupply(), 'supplier' => $supplier));
    }

    /**
     * @Route("/admin/supply/edit/{id}", name="editsupply")
     *
     **/
    public function editSupply($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $supply = $em->getRepository(Supply::class)->findOneBy(['userId' => $user, 'id' => $id]);
        if ($supply) {
            $supplier = $em->getRepository(Supplier::class)->findBy(['userId' => $user]);
            $supplyitems = $em->getRepository(SupplyItem::class)->findBySupplyStock($id, $user);
            $stockitems = $this->getDoctrine()->getRepository(Stock::class)->findBy(['userId' => $user, 'deleted' => NULL]);

            return $this->render('Stock/editsupply.html.twig', ['title' => 'Supplies', 'supply' => $supply, 'supplier' => $supplier, 'supplyitems' => $supplyitems, 'stockitems' => $stockitems]);
        }
    }

    /**
     * @Route("/admin/supply/update/{id}", name="updatesupply")
     *
     * @return void
     * @author 
     **/
    public function updateSupply($id)
    {
        if (isset($_POST['supplier'])) {

            $user = $this->getUser()->getId();
            $em = $this->getDoctrine()->getManager();
            $supply = $em->getRepository(Supply::class)->findOneBy(['userId' => $user, 'id' => $id]);
            // $supply->setName($_POST['name']);
            $supply->setSupplier($em->getRepository(Supplier::class)->findOneBy(['id' => $_POST['supplier'], 'userId' => $user]));
            $total = array_sum($_POST['amount']) + $supply->getTotal();
            $supply->setTotal($total);
            $supply->setDate(\DateTime::createFromFormat('d/m/Y', $_POST['date']));
            $em->persist($supply);
            $em->flush();
            $this->supplyItemAction($supply->getId(), $_POST);
            
            return $this->redirectToRoute('supplyitems', array('id' => $id));
        }
    }

    /**
     * @Route("/admin/supply/delete/{id}", name="deletesupply")
     *
     **/
    public function deleteSupply($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $supply = $em->getRepository(Supply::class)->findOneBy(['userId' => $user, 'id' => $id]);
        
        if ($supply) {
            $supplyitems = $em->getRepository(SupplyItem::class)->findBy(['userId' => $user, 'supplyId' => $id]);
            foreach ($supplyitems as $item) {
                // $this->deleteSupplyItem($item->getId());
                $stock = $em->getRepository(Stock::class)->findOneBy(['id' => $item->getStock(), 'userId' => $user]);
                if ($stock) {
                    $stock->setQuantity(max($stock->getQuantity() - $item->getQuantity(), 0));
                    $em->persist($stock);
                }
            }
            $em->getRepository(SupplyItem::class)->deleteSupplyItems($id, $user);

            $em->remove($supply);
            $em->flush();

            // return $this->json(['message' => 'Supply deleted successfully']);
        }

        return $this->redirectToRoute('supplies');
    }

    public function supplyItemAction($id, $items)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        for($i=0; $i<count($items['item']); $i++) {
            if ($items['item'][$i] !== "-1") {
                $stock = $this->getDoctrine()->getRepository(Stock::class)->findOneBy([
                    'id' => explode("|", $items['item'][$i])[0], 
                    'userId' => $user
                ]);
            }else {
                $stock = new Stock();
                $stock->setName($items['stock'][$i]);
                $stock->setUnit($items['unit'][$i]);
                $stock->setUserId($user);
            }
            $stock->setQuantity($stock->getQuantity() + floatval($items['quantity'][$i]));
            $em->persist($stock);
            $em->flush();
            $item = new SupplyItem();
            $item->setSupplyId($id);
            $item->setUserId($user);
            // $item->setName($items['stock'][$i]);
            $item->setStock($stock->getId());
            $item->setQuantity(floatval($items['quantity'][$i]));
            $item->setAmount(floatval($items['amount'][$i]));
            
            $em->persist($item);
        }
        $em->flush();
    }

    /**
     * show/add spillages
     *
     * @Route("/admin/spillage", name="spillage")
     **/
    public function spillage()
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $spillage = $em->getRepository(Spillage::class)->findBy(['userId' => $user]);
        $inventory = $em->getRepository(Stock::class)->findBy(['userId' => $user]);

        return $this->render('Stock/spillage.html.twig', array('title' => 'Spillage', 'spillages' => $spillage, 'inventory' => $inventory));
    }

    /**
     * post spillages
     *
     * @Route("/admin/spillage/post", name="post_spillage", methods="POST")
     **/
    public function postSplillage()
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $stock = $em->getRepository(Stock::class)->findOneBy(['userId' => $user, 'id' => $_POST['stock']]);
        $splillage = new Spillage();
        $splillage->setReason($_POST['reason']);
        $splillage->setStock($stock);
        $splillage->setQuantity($_POST['quantity']);
        $splillage->setUserId($user);
        $splillage->setCreated(\DateTime::createFromFormat('d/m/Y', $_POST['date']));
        $stock = $em->getRepository(Stock::class)->findOneBy(['userId' => $user, 'id' => $_POST['stock']]);
        if ($stock) {
            $em->persist($splillage);
            $stock->setQuantity(max($stock->getQuantity() - $_POST['quantity'], 0));
            $em->persist($stock);
            $em->flush();
        }
        
        return $this->redirectToRoute('spillage');
    }

    /**
     * delete spillage
     *
     * @Route("/admin/spillage/delete/{id}", name="delete_spillage")
     **/
    public function deleteSpillage($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $spillage = $em->getRepository(Spillage::class)->findOneBy(['userId' => $user, 'id' => $id]);
        if ($spillage) {
            $stock = $em->getRepository(Stock::class)->findOneBy(['userId' => $user, 'id' => $spillage->getStock()]);
            $stock->setQuantity($stock->getQuantity() + $spillage->getQuantity());
            $em->remove($spillage);
            $em->persist($stock);
            $em->flush();
        }

        return $this->redirectToRoute('spillage');
    }

    /**
     * @Route("/admin/supply/item/delete/{id}", name="deleteSupplyItem")
     *
     **/
    public function deleteSupplyItem($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(SupplyItem::class)->findOneBy(['userId' => $user, 'id' => $id]);
        $stock = $em->getRepository(Stock::class)->findOneBy(['userId' => $user, 'id' => $item->getStock()]);
        $supply = $em->getRepository(Supply::class)->findOneBy(['userId' => $user, 'id' => $item->getSupplyId()]);
        try {
            $stock->setQuantity(max($stock->getQuantity() - $item->getQuantity(), 0));
            $supply->setTotal(max($supply->getTotal() - $item->getAmount(), 0));
        } catch (\Exception $e) {
            return $this->json(['message' => 'Coudn\'t delete item']);
        }
        $em->persist($stock);
        $em->persist($supply);
        $em->remove($item);
        $em->flush();

        return $this->json(['message' => 'Item deleted succesfully']);
    }

    /**
     * @Route("/admin/supplier/{id}", name="supplier")
     */
    public function supplierAction($id = null)
    {
        $user = $this->getUser()->getId();

        $suppliers = $this->getDoctrine()->getRepository(Supplier::class)->findBy(['userId' => $user]);

        $supplier = [];
        if ($id) {
            $supplier = $this->getDoctrine()->getRepository(Supplier::class)->findOneBy(['id' => $id, 'userId' => $user]);
        }

        if (isset($_POST['name'])) {
            if (isset($_POST['edit'])) {
                $supplier = $this->getDoctrine()->getRepository(Supplier::class)->findOneBy(['id' => $_POST['edit'], 'userId' => $user]);
            }else{
                $supplier = new Supplier();
                $supplier->setUserId($user);
            }
            $supplier->setName($_POST['name']);
            $supplier->setAddress($_POST['address']);
            $supplier->setTelephone($_POST['telephone']);
            $supplier->setEmail($_POST['email']);
            $em = $this->getDoctrine()->getManager();
            $em->persist($supplier);
            $em->flush();

            $flash = isset($_POST['edit']) ? 'Supplier updated' : 'Supplier added';

            $this->addFlash(
                'success',
                $flash
            );
            
            return $this->redirectToRoute('supplier');
        }

        return $this->render('Stock/supplier.html.twig', array('title' => 'Suppliers', 'suppliers' => $suppliers, 'supplier' => $supplier));
    }

    /**
     * @Route("/admin/supplier/delete/{id}", name="deletesupplier")
     *
     * @param int $id Supplier ID
     **/
    public function deleteSupplier($id)
    {
        // return $this->json(['hello']);
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $supplyitems = $em->getRepository(SupplyItem::class)->suppierItems($id, $user);
        // var_dump($supplyitems);die;
        foreach ($supplyitems as $item) {
            $stock = $em->getRepository(Stock::class)->findOneBy(['id' => $item['stock'], 'userId' => $user]);
            if ($stock) {
                $stock->setQuantity(max($stock->getQuantity() - $item['quantity'], 0));
                $em->persist($stock);
            }
        }
        
        $supplier = $em->getRepository(Supplier::class)->findOneBy(['userId' => $user, 'id' => $id]);
        $name = $supplier->getName();
        foreach ($supplier->getSupply() as $supply) {
            $em->getRepository(SupplyItem::class)->deleteSupplyItems($supply->getId(), $user);
        }
        $em->getRepository(Supply::class)->deleteSuppliesBySupplier($id, $user);
        $em->remove($supplier);
        $em->flush();

        $flash = $name . ' deleted';

        $this->addFlash(
            'success',
            $flash
        );
        // return $this->json($supplies);
        return $this->redirectToRoute('supplier');
    }


    /**
     * @Route("/admin/stocks", name="stocks")
     */
    // public function categoryAction()
    // {
    //     $user = $this->getUser()->getId();

    //     $item = $this->getDoctrine()->getRepository(Item::class)->findBy(['userId' => $user]);
    //     $inventory = $this->getDoctrine()->getRepository(Stock::class)->findAllItem();

    //     $stock = null;
    //     if (isset($_GET['id'])) {
    //         $stock = $this->getDoctrine()->getRepository(Stock::class)->findBy(['id' => $_GET['id'], 'userId' => $user]);
    //     }

    //     if (isset($_POST['submit'])) {
    //         if (isset($_POST['edit'])) {
    //             $stock = $this->getDoctrine()->getRepository(Stock::class)->findBy(['id' => $_POST['edit'], 'userId' => $user]);
    //         }else{
    //             $stock = new Stock();
    //             $stock->setUserId($user);
    //         }
    //         $stock->setName($_POST['name']);
    //         $stock->setGId($_POST['group']);
    //         $em = $this->getDoctrine()->getManager();
    //         $em->persist($stock);
    //         $em->flush();
            
    //         return $this->redirectToRoute('stock');
    //     }

    //     return $this->render('Stock:index.html.twig', array('title' => 'Stock', 'inventory' => $inventory, 'group' => $group, 'stock' => $stock));
    // }
}
