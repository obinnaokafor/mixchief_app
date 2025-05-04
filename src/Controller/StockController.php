<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\Spillage;
use App\Entity\Stock;
use App\Entity\Supplier;
use App\Entity\Supply;
use App\Entity\SupplyItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StockController extends Controller
{
    #[Route("/admin/stock", name: "stock")]
    public function stock()
    {
        $user = $this->getUser()->getId();
        $stock = $this->em->getRepository(Stock::class)->findBy(['userId' => $user, 'deleted' => null], ['name' => 'ASC']);
        $stockitem = [];
        // Get stock item to edit
        if (isset($_GET['id'])) {
            $stockitem = $this->em->getRepository(Stock::class)->findOneBy(['id' => $_GET['id'], 'userId' => $user]);
        }
        if (isset($_POST['name'])) {
            // check if its an update then update item with id else add new stock items
            if (isset($_POST['edit'])) {
                $stockitem = $this->em->getRepository(Stock::class)->findOneBy(['id' => $_POST['edit'], 'userId' => $user]);
                $stockitem->setName($_POST['name'][0]);
                $stockitem->setUnit($_POST['unit'][0]);
                $stockitem->setQuantity(isset($_POST['quantity'][0]) ? $_POST['quantity'][0] : 0);
                $this->em->persist($stockitem);
            } else {
                for ($i = 0; $i < count($_POST['name']); ++$i) {
                    $stockitem = new Stock();
                    $stockitem->setName($_POST['name'][$i]);
                    $stockitem->setUserId($user);
                    $stockitem->setUnit($_POST['unit'][$i]);
                    $stockitem->setQuantity($_POST['quantity'][$i] ?: 0);
                    $this->em->persist($stockitem);
                }
            }
            $this->em->flush();

            return $this->redirectToRoute('stock');
        }

        return $this->render('Stock/index.html.twig', ['title' => 'Stock', 'stock' => $stock, 'stockitem' => $stockitem]);
    }

    #[Route("/admin/stock/sales/{id}", name: "stock_sales")]
    public function inventorySales($id)
    {
        $user = $this->getUser()->getId();
    }

    #[Route("/admin/stock/ajax/{page}", name: "stock_ajax")]
    public function ajax($page): Response
    {
        return $this->render("ajax/$page.html.twig");
    }

    #[Route("/admin/stock/supply/{supplierId}", name: "stock_supply")]
    public function supply($supplierId = null)
    {
        $user = $this->getUser()->getId();
        $suppliers = $this->em->getRepository(Supplier::class)->findBy(['userId' => $user]);
        $items = $this->em->getRepository(Stock::class)->findBy(['userId' => $user, 'deleted' => null]);

        if (isset($_POST['supplier'])) {
            $date = date('m/d/Y H:i:s');
            $supply = new Supply();
            $supply->setSupplier($this->em->getRepository(Supplier::class)->findOneBy(['userId' => $user, 'id' => $_POST['supplier']]));
            $total = array_sum($_POST['amount']);
            $supply->setTotal($total);
            $supply->setUserId($user);
            $supply->setDate(\DateTime::createFromFormat('d/m/Y', $_POST['date']));
            $this->em->persist($supply);
            $this->em->flush();
            $this->supplyItemAction($supply->getId(), $_POST);

            return $this->redirectToRoute('stock_supply_items', ['id' => $supply->getId()]);
        }

        return $this->render('Stock/supply.html.twig', ['title' => 'Enter Supply', 'suppliers' => $suppliers, 'items' => $items, 'supplierId' => $supplierId]);
    }

    #[Route("/admin/stock/supplies", name: "stock_supplies")]
    public function supplies(): Response
    {
        $user = $this->getUser()->getId();
        $supplies = $this->em->getRepository(Supply::class)->findBy(['userId' => $user]);

        return $this->render('Stock/supplies.html.twig', ['title' => 'Supplies', 'supplies' => $supplies]);
    }

    #[Route("/admin/stock/supply/items/{id}", name: "stock_supply_items")]
    public function supplyItems($id): Response
    {
        $user = $this->getUser()->getId();
        $supply = $this->em->getRepository(Supply::class)->findOneBy(['id' => $id, 'userId' => $user]);
        $supplies = $this->em->getRepository(SupplyItem::class)->findSupplyItems($id, $user);

        return $this->render('Stock/itemSupplies.html.twig', ['title' => 'Supplies', 'supplies' => $supplies, 'supply' => $supply]);
    }

    #[Route("/admin/stock/supply/items/stock/{id}", name: "stock_supply_items_stock")]
    public function supplyItemsByStock($id): Response
    {
        $user = $this->getUser()->getId();
        $stock = $this->em->getRepository(Stock::class)->findOneBy(['id' => $id, 'userId' => $user]);
        $supplies = $this->em->getRepository(SupplyItem::class)->findSupplyItemsByStock($id, $user);

        return $this->render('Stock/stocksupplies.html.twig', ['title' => 'Stock Supplies', 'supplies' => $supplies, 'stock' => $stock]);
    }

    #[Route("/admin/stock/supplies/stock/{id}", name: "stock_supplies_stock")]
    public function suppliesByStock($id)
    {
        $supplies = $this->em->getRepository(SupplyItem::class)->findAllSupplyItem($user);
    }

    #[Route("/admin/stock/supplies/supplier/{id}", name: "stock_supplies_supplier")]
    public function suppliesBySupplier($id): Response
    {
        $user = $this->getUser()->getId();
        $supplier = $this->em->getRepository(Supplier::class)->findOneBy(['userId' => $user, 'id' => $id]);

        return $this->render('Stock/supplies.html.twig', ['title' => 'Supplies', 'supplies' => $supplier->getSupply(), 'supplier' => $supplier]);
    }

    #[Route("/admin/stock/supply/edit/{id}", name: "stock_supply_edit")]
    public function editSupply($id): Response
    {
        $user = $this->getUser()->getId();
        $supply = $this->em->getRepository(Supply::class)->findOneBy(['userId' => $user, 'id' => $id]);
        if ($supply) {
            $supplier = $this->em->getRepository(Supplier::class)->findBy(['userId' => $user]);
            $supplyitems = $this->em->getRepository(SupplyItem::class)->findBySupplyStock($id, $user);
            $stockitems = $this->em->getRepository(Stock::class)->findBy(['userId' => $user, 'deleted' => null]);

            return $this->render('Stock/editsupply.html.twig', ['title' => 'Supplies', 'supply' => $supply, 'supplier' => $supplier, 'supplyitems' => $supplyitems, 'stockitems' => $stockitems]);
        }
    }

    #[Route("/admin/stock/supply/update/{id}", name: "stock_supply_update")]
    public function updateSupply($id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (isset($_POST['supplier'])) {
            $user = $this->getUser()->getId();
            $supply = $this->em->getRepository(Supply::class)->findOneBy(['userId' => $user, 'id' => $id]);
            // $supply->setName($_POST['name']);
            $supply->setSupplier($this->em->getRepository(Supplier::class)->findOneBy(['id' => $_POST['supplier'], 'userId' => $user]));
            $total = array_sum($_POST['amount']) + $supply->getTotal();
            $supply->setTotal($total);
            $supply->setDate(\DateTime::createFromFormat('d/m/Y', $_POST['date']));
            $this->em->persist($supply);
            $this->em->flush();
            $this->supplyItemAction($supply->getId(), $_POST);

            return $this->redirectToRoute('stock_supply_items', ['id' => $id]);
        }
    }

    #[Route("/admin/stock/supply/delete/{id}", name: "stock_supply_delete")]
    public function deleteSupply($id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $user = $this->getUser()->getId();
        $supply = $this->em->getRepository(Supply::class)->findOneBy(['userId' => $user, 'id' => $id]);

        if ($supply) {
            $supplyitems = $this->em->getRepository(SupplyItem::class)->findBy(['userId' => $user, 'supplyId' => $id]);
            foreach ($supplyitems as $item) {
                // $this->deleteSupplyItem($item->getId());
                $stock = $this->em->getRepository(Stock::class)->findOneBy(['id' => $item->getStock(), 'userId' => $user]);
                if ($stock) {
                    $stock->setQuantity(max($stock->getQuantity() - $item->getQuantity(), 0));
                    $this->em->persist($stock);
                }
            }
            $this->em->getRepository(SupplyItem::class)->deleteSupplyItems($id, $user);

            $this->em->remove($supply);
            $this->em->flush();

            // return $this->json(['message' => 'Supply deleted successfully']);
        }

        return $this->redirectToRoute('stock_supplies');
    }

    #[Route("/admin/stock/supply/item/{id}/{items}", name: "stock_supply_item")]
    public function supplyItem($id, $items)
    {
        $user = $this->getUser()->getId();
        for ($i = 0; $i < count($items['item']); ++$i) {
            if ($items['item'][$i] !== '-1') {
                $stock = $this->em->getRepository(Stock::class)->findOneBy([
                    'id' => explode('|', $items['item'][$i])[0],
                    'userId' => $user,
                ]);
            } else {
                $stock = new Stock();
                $stock->setName($items['stock'][$i]);
                $stock->setUnit($items['unit'][$i]);
                $stock->setUserId($user);
            }
            $stock->setQuantity($stock->getQuantity() + floatval($items['quantity'][$i]));
            $this->em->persist($stock);
            $this->em->flush();
            $item = new SupplyItem();
            $item->setSupplyId($id);
            $item->setUserId($user);
            // $item->setName($items['stock'][$i]);
            $item->setStock($stock->getId());
            $item->setQuantity(floatval($items['quantity'][$i]));
            $item->setAmount(floatval($items['amount'][$i]));

            $this->em->persist($item);
        }
        $this->em->flush();
    }

    #[Route("/admin/stock/spillage", name: "stock_spillage")]
    public function spillage(): Response
    {
        $user = $this->getUser()->getId();
        $spillage = $this->em->getRepository(Spillage::class)->findBy(['userId' => $user]);
        $inventory = $this->em->getRepository(Stock::class)->findBy(['userId' => $user]);

        return $this->render('Stock/spillage.html.twig', ['title' => 'Spillage', 'spillages' => $spillage, 'inventory' => $inventory]);
    }

    #[Route("/admin/stock/spillage/post", name: "stock_spillage_post")]
    public function postSplillage(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $user = $this->getUser()->getId();
        $stock = $this->em->getRepository(Stock::class)->findOneBy(['userId' => $user, 'id' => $_POST['stock']]);
        $splillage = new Spillage();
        $splillage->setReason($_POST['reason']);
        $splillage->setStock($stock);
        $splillage->setQuantity($_POST['quantity']);
        $splillage->setUserId($user);
        $splillage->setCreated(\DateTime::createFromFormat('d/m/Y', $_POST['date']));
        $stock = $this->em->getRepository(Stock::class)->findOneBy(['userId' => $user, 'id' => $_POST['stock']]);
        if ($stock) {
            $this->em->persist($splillage);
            $stock->setQuantity(max($stock->getQuantity() - $_POST['quantity'], 0));
            $this->em->persist($stock);
            $this->em->flush();
        }

        return $this->redirectToRoute('stock_spillage');
    }

    #[Route("/admin/stock/spillage/delete/{id}", name: "stock_spillage_delete")]
    public function deleteSpillage($id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $user = $this->getUser()->getId();
        $spillage = $this->em->getRepository(Spillage::class)->findOneBy(['userId' => $user, 'id' => $id]);
        if ($spillage) {
            $stock = $this->em->getRepository(Stock::class)->findOneBy(['userId' => $user, 'id' => $spillage->getStock()]);
            $stock->setQuantity($stock->getQuantity() + $spillage->getQuantity());
            $this->em->remove($spillage);
            $this->em->persist($stock);
            $this->em->flush();
        }

        return $this->redirectToRoute('stock_spillage');
    }

    #[Route("/admin/stock/supply/item/delete/{id}", name: "stock_supply_item_delete")]
    public function deleteSupplyItem($id): JsonResponse
    {
        $user = $this->getUser()->getId();
        $item = $this->em->getRepository(SupplyItem::class)->findOneBy(['userId' => $user, 'id' => $id]);
        $stock = $this->em->getRepository(Stock::class)->findOneBy(['userId' => $user, 'id' => $item->getStock()]);
        $supply = $this->em->getRepository(Supply::class)->findOneBy(['userId' => $user, 'id' => $item->getSupplyId()]);
        try {
            $stock->setQuantity(max($stock->getQuantity() - $item->getQuantity(), 0));
            $supply->setTotal(max($supply->getTotal() - $item->getAmount(), 0));
        } catch (\Exception $e) {
            return $this->json(['message' => 'Coudn\'t delete item']);
        }
        $this->em->persist($stock);
        $this->em->persist($supply);
        $this->em->remove($item);
        $this->em->flush();

        return $this->json(['message' => 'Item deleted succesfully']);
    }

    #[Route("/admin/stock/supplier/{id}", name: "stock_supplier")]
    public function supplier($id = null)
    {
        $user = $this->getUser()->getId();

        $suppliers = $this->em->getRepository(Supplier::class)->findBy(['userId' => $user]);

        $supplier = [];
        if ($id) {
            $supplier = $this->em->getRepository(Supplier::class)->findOneBy(['id' => $id, 'userId' => $user]);
        }

        if (isset($_POST['name'])) {
            if (isset($_POST['edit'])) {
                $supplier = $this->em->getRepository(Supplier::class)->findOneBy(['id' => $_POST['edit'], 'userId' => $user]);
            } else {
                $supplier = new Supplier();
                $supplier->setUserId($user);
            }
            $supplier->setName($_POST['name']);
            $supplier->setAddress($_POST['address']);
            $supplier->setTelephone($_POST['telephone']);
            $supplier->setEmail($_POST['email']);
            $this->em->persist($supplier);
            $this->em->flush();

            $flash = isset($_POST['edit']) ? 'Supplier updated' : 'Supplier added';

            $this->addFlash(
                'success',
                $flash
            );

            return $this->redirectToRoute('stock_supplier');
        }

        return $this->render('Stock/supplier.html.twig', ['title' => 'Suppliers', 'suppliers' => $suppliers, 'supplier' => $supplier]);
    }

    #[Route("/admin/stock/supplier/delete/{id}", name: "stock_supplier_delete")]
    public function deleteSupplier($id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        // return $this->json(['hello']);
        $user = $this->getUser()->getId();
        $supplyitems = $this->em->getRepository(SupplyItem::class)->suppierItems($id, $user);
        // var_dump($supplyitems);die;
        foreach ($supplyitems as $item) {
            $stock = $this->em->getRepository(Stock::class)->findOneBy(['id' => $item['stock'], 'userId' => $user]);
            if ($stock) {
                $stock->setQuantity(max($stock->getQuantity() - $item['quantity'], 0));
                $this->em->persist($stock);
            }
        }

        $supplier = $this->em->getRepository(Supplier::class)->findOneBy(['userId' => $user, 'id' => $id]);
        $name = $supplier->getName();
        foreach ($supplier->getSupply() as $supply) {
            $this->em->getRepository(SupplyItem::class)->deleteSupplyItems($supply->getId(), $user);
        }
        $this->em->getRepository(Supply::class)->deleteSuppliesBySupplier($id, $user);
        $this->em->remove($supplier);
        $this->em->flush();

        $flash = $name.' deleted';

        $this->addFlash(
            'success',
            $flash
        );

        // return $this->json($supplies);
        return $this->redirectToRoute('stock_supplier');
    }

    /*
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
