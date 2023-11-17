<?php

namespace App\Controller;

use App\Controller\Controller;
// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Item;
use App\Entity\ItemPart;
use App\Entity\Category;
use App\Entity\Groups;
use App\Entity\Orders;
use App\Entity\Stock;
use App\Entity\OrderItem;
use App\Entity\Supply;
use App\Entity\SupplyItem;

class InventoryController extends Controller
{

    /**
     * @Route("/admin/items", name="registerItem", methods={"POST"})
     */
    public function registerAction(Request $request)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        // $data = json_decode($request->getContent(), true);
        $data = $_POST;
        // return $this->json($data);
        for ($i=0; $i < count($data['name']); $i++) {
            $item = $em->getRepository(Item::class)->findOneBy(['name' => $data['name'][$i], 'userId' => $user]);
            $category = $em->getRepository(Groups::class)->findOneBy(['userId' => $user, 'id' => $data['category'][$i]]);

            // If there is a duplicate name, check if the item was soft deleted else return error
            if ($item && $item->getDeleted()) {
                $item->setDeleted(NULL);
            }elseif($item){
                return $this->json(['message' => 'Item name ' . $data['name'][$i] . ' already exists']);
            }
            else {
                $item = new Item;
                $stock = new Stock;
                $item->setDiscount(0);
                $item->setUserId($user);
                $item->setName($data['name'][$i]);
                $stock->setName($data['name'][$i]);
                $stock->setQuantity(0);
                $stock->setUserId($user);
                
            }

            if ($image = $this->upload('image', 300)) {
                $item->setImg($image);
            }

            $item->setSellingPrice($data['selling'][$i]);
            $item->setDescription($data['description'][$i]);
            $item->setCId($data['category'][$i]);
            $item->setCategory($category);
            // $item->setIcon($data['icon']);

            $em->persist($item);
            $em->persist($stock);
            $em->flush();
            
            $part = new ItemPart;
            $part->setItemId($item->getId());
            $part->setStockId($stock->getId());
            $part->setPortion(1);
            $part->setUserId($user);
            $em->persist($part);
        }
        $em->flush();
        $url = $this->generateUrl(
            'item',
            ['id' => $item->getId()]
        );


        return $this->redirectToRoute('item');

        // return $this->json($item, 201, ['Location' => $url]);
    }

    /**
     * @Route("/admin/inventory/all", name="allinventory")
     *
     * @return JsonResponse
     **/
    public function inventory()
    {
        $user = $this->getUser()->getId();
        $stock = $this->getDoctrine()->getRepository(Stock::class)->findBy(['userId' => $user, 'deleted' => NULL]);

        return $this->json($stock);
    }

    /**
     * Delete an Entity's related entities if entity is one of item, category, supply, orders, stock
     *
     * @param string $query Entity name
     * @param int $id
     *
     * @Route("/admin/delete/{query}/{id}", name="delete")
     */
    public function delete($query, $id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository("App\Entity\\$query")->findOneBy(['id' => $id, 'userId' => $user]);


        if ($item && in_array($query, ['Category', 'Supply', 'Orders', 'Item', 'Stock', 'Supplier'])) {
            switch ($query) {
                case 'Supplier':
                    $this->getDoctrine()->getRepository(Supply::class)->deleteSuppliesBySupplier($id, $user);
                    break;
                case 'Supply':
                    $this->getDoctrine()->getRepository(SupplyItem::class)->deleteSupplyItems($id, $user);
                    break;
                case 'Item':
                    $this->getDoctrine()->getRepository(ItemPart::class)->deleteByItem($id, $user);
                    break;
                case 'Stock':
                    $this->getDoctrine()->getRepository(ItemPart::class)->deleteByStock($id, $user);
                    break;
                case 'Orders':
                    $this->getDoctrine()->getRepository(OrderItem::class)->deleteByOrder($id, $user);
                    break;
                case 'Category':
                    $this->getDoctrine()->getRepository(Item::class)->deleteByCategory($id, $user);
                    break;
                default:

                    break;
            }
            // foreach ($items as $individual) {
            //     $em->remove($individual);
            // }
        }
        // $em->remove($item);
        // $em->flush();
        $route = strtolower($query);
        return $route;
    }

    /**
     * List items or show an individual item
     *
     * @param int | null $id item id
     *
     * @Route("/admin/items/{id}", name="item", methods={"GET"})
     */
    public function itemsAction($id = null)
    {
        $user = $this->getUser()->getId();
        // dd($user); 
        if ($id) {
            $item = $this->getDoctrine()->getRepository(Item::class)->find($id);
            $itempart = $this->getDoctrine()->getRepository(ItemPart::class)->findItem($id, $user);
            // var_dump($itempart);die;
            return $this->render('Inventory/item.html.twig', array('title' => 'Item', 'item' => $item, 'itempart' => $itempart));
        }
        $items = $this->getDoctrine()->getRepository(Item::class)->findBy(['userId' => $user, 'deleted' => NULL]);
        $category = $this->getDoctrine()->getRepository(Groups::class)->findBy(['userId' => $user]);

        if (!$category) {
            $url = $this->generateUrl('group');

            $this->addFlash(
                'warning',
                'At least one category is required before adding items.'
            );
        }
        $stock = $this->getDoctrine()->getRepository(Stock::class)->findBy(['userId' => $user]);

        return $this->render('Inventory/items.html.twig', array('title' => 'Items', 'items' => $items, 'category' => $category, 'stock' => $stock));
    }

    /**
     * @Route("/admin/items/delete/{id}", name="removeitem")
     *
     * @param int $id Item ID
     **/
    public function removeItem($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(Item::class)->findOneBy(['id' => $id, 'userId' => $user]);
        $this->delete('Item', $id);

        $item->setDeleted(true);
        $em->persist($item);
        $em->flush();

        return $this->redirectToRoute('item');
    }

    /**
     * @Route("/admin/inventory/delete/{id}", name="removeinventoryitem")
     *
     * @param int $id Inventory ID
     **/
    public function removeInventoryItem($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $stock = $em->getRepository(Stock::class)->findOneBy(['id' => $id, 'userId' => $user]);
        $this->delete('Stock', $id);

        $stock->setDeleted(true);
        $em->persist($stock);
        $em->flush();

        return $this->redirectToRoute('stock');
    }

    /**
     * Delete Item part
     *
     * @param int $id
     * @Route("/admin/stockitem/remove/{id}", name="removeStockItem")
     */
    public function stockItemRemoveAction($id)
    {
        $user = $this->getUser()->getId();
        $itempart = $this->getDoctrine()->getRepository(ItemPart::class)->findOneBy(['id' => $id, 'userId' => $user]);
        $em = $this->getDoctrine()->getManager();
        $em->remove($itempart);
        $em->flush();
        return $this->redirectToRoute('item', array('id' => $itempart->getItemId()));
    }



    /**
     * @Route("/admin/edititem", name="editItem")
     */
    public function editAction()
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        if (isset($_POST['submit'])) {
            $item = $this->getDoctrine()->getRepository(Item::class)->findOneBy(['id' => $_POST['id'], 'userId' => $user]);
            $category = $em->getRepository(Groups::class)->findOneBy(['userId' => $user, 'id' => $_POST['category']]);
            $item->setName($_POST['name']);
            $item->setSellingPrice($_POST['selling']);
            $item->setCId($_POST['category']);
            $item->setCategory($category);
            $item->setDescription($_POST['description']);
            // if ($image = $this->upload('image', 300)) {
            //     $item->setImg($image);
            // }
            $em->persist($item);
            $em->flush();
            if (!$_POST['stock']) {
                # code...
            }else {
                for ($i=0; $i < sizeof($_POST['stock']); $i++) {
                    if ($_POST['stock'][$i]) {
                        $stockId = explode("|", $_POST['stock'][$i])[0];
                        $stocks = new ItemPart();
                        $stocks->setItemId($item->getId());
                        $stocks->setStockId($stockId);
                        $stocks->setPortion($_POST['portion'][$i] ?: 1);
                        $stocks->setUserId($user);
                        $em->persist($stocks);
                    }
                }
                $em->flush();
            }


            return $this->redirectToRoute('item', array('id' => $_POST['id']));
        }else {
            $item = $this->getDoctrine()->getRepository(Item::class)->findOneBy(['id' => $_GET['id'], 'userId' => $user]);
            $category = $this->getDoctrine()->getRepository(Groups::class)->findBy(['userId' => $user]);
            $stock = $this->getDoctrine()->getRepository(Stock::class)->findBy(['userId' => $user]);
            $itempart = $this->getDoctrine()->getRepository(ItemPart::class)->findItem($_GET['id'], $user);
            $id = $_GET['id'];
        }

        return $this->render('Inventory/edititem.html.twig', array('title' => 'Item', 'category' => $category, 'item' => $item, 'id' => $id, 'stock' => $stock, 'itempart' => $itempart));
    }

    /**
     * @Route("/admin/category-old", name="category")
     */
    public function categoryAction()
    {
        $user = $this->getUser()->getId();

        $group = $this->getDoctrine()->getRepository(Groups::class)->findBy(['userId' => $user]);
        $categories = $this->getDoctrine()->getRepository(Category::class)->findAllGroup($user);

        $category = null;
        if (isset($_GET['id'])) {
            $category = $this->getDoctrine()->getRepository(Category::class)->findOneBy(['id' => $_GET['id'], 'userId' => $user]);
        }

        if (isset($_POST['submit'])) {
            if (isset($_POST['edit'])) {
                $category = $this->getDoctrine()->getRepository(Category::class)->findOneBy(['id' => $_POST['id'], 'userId' => $user]);
            }else{
                $response = $this->check('Category', 'name', $_POST['name'], 'category');
                if ($response) {
                    return $response;
                }
                $category = new Category();
                $category->setUserId($user);
            }
            $category->setName($_POST['name']);
            $category->setGId($_POST['group']);
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            return $this->redirectToRoute('category');
        }

        return $this->render('Inventory/category.html.twig', array('title' => 'Categories', 'categories' => $categories, 'group' => $group, 'category' => $category));
    }

    /**
     * @Route("/admin/category", name="group")
     */
    public function groupAction()
    {
        $user = $this->getUser()->getId();

        $groups = $this->getDoctrine()->getRepository(Groups::class)->findBy(['userId' => $user]);

        $group = null;
        if (isset($_GET['id'])) {
            $group = $this->getDoctrine()->getRepository(Groups::class)->findOneBy(['id' => $_GET['id'], 'userId' => $user]);
        }

        if (isset($_POST['submit'])) {
            if (isset($_POST['edit'])) {
                $group = $this->getDoctrine()->getRepository(Groups::class)->findOneBy(['id' => $_POST['edit'], 'userId' => $user]);
            }else{
                $response = $this->check('Groups', 'name', $_POST['name'], 'group');
                if ($response) {
                    return $response;
                }
                $group = new Groups();
                $group->setUserId($user);
            }
            $group->setName($_POST['name']);
            $em = $this->getDoctrine()->getManager();
            $em->persist($group);
            $em->flush();

            return $this->redirectToRoute('group');
        }

        return $this->render('Inventory/group.html.twig', array('title' => 'Categories', 'group' => $group, 'groups' => $groups));
    }

    /**
     * @Route("/admin/category/delete/{id}", name="deletecategory")
     */
    public function deleteCategory($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository(Groups::class)->findOneBy(['id' => $id, 'userId' => $user]);
        $items = $em->getRepository(Item::class)->findBy(['cId' => $id, 'userId' => $user]);
        foreach ($items as $item) {
            $this->delete('Item', $item->getId());
            // $em->remove($item);
        }
        $em->getRepository(Item::class)->deleteByCategory($category, $user);
        $em->remove($category);
        $em->flush();

        return $this->redirectToRoute('group');

    }

    /**
     * @Route("/admin/items/delete/{id}", name="removeitem")
     *
     * @param int $id Item ID
     **/
    public function removeSupplier($id)
    {
        $user = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $supplier = $em->getRepository(Supplier::class)->findOneBy(['id' => $id, 'userId' => $user]);
        $supplies = $em->getRepository(Supply::class)->findBy(['supplier_id' => $id, 'user_id' => $user]);
        $this->delete('Supplier', $id);
        foreach ($supplies as $key => $supply) {
            $this->delete('Supply', $supply->getId());
        }

        $supplier->setDeleted(true);
        $em->persist($supplier);
        $em->flush();

        return $this->redirectToRoute('supplier');
    }

    public function check($entity, $name, $value)
    {
        $user = $this->getUser()->getId();
        $item = $this->getDoctrine()->getRepository('App\Entity\\' . $entity)->findOneBy(array($name => $value, 'userId' => $user));
        // var_dump($item);
        if ($item) {
            if ($item->getDeleted()) {
                return $item->getId();
            }
            return $this->json("$name is already in use");
        }else {
            return false;
        }
    }
}
