<?php

namespace App\Controller;

use App\Controller\Controller;
// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Groups;
use App\Entity\Item;
use App\Entity\Supplier;

/**
 * Class to handle all API requests
 */
class ApiController extends Controller
{

	/**
	 * @Route("/api/category", name="api_category")
	 *
	 * @return JsonResponse
	 **/
	public function category()
	{
		$data = $this->decode();
		$em = $this->getDoctrine()->getManager();
		$user = $this->getUser()->getId();

		$cat = new Groups();
		$cat->setName($data['name']);
		$cat->setUserId($user);

		$em->persist($cat);
		$em->flush();

		$this->addFlash(
		    'success',
		    'Category ' . $data['name'] . ' added'
		);


		return $this->json(['status' => 'success']);
	}

	/**
	 * @Route("/api/items", name="api_items")
	 */
	public function getItems(Request $request)
	{
		// fetch items from DB with id in list from post request
		// return json response
		$data = $this->decode();
		$items = $this->getDoctrine()->getRepository(Item::class)->findOrderItemsFromList($data, $this->user()->getId());
		return $this->json(['status' => 'success', 'items' => $items, 'delivery' => 1500]);
	}

	/**
	 * @Route("/api/category", name="api_category")
	 *
	 * @return JsonResponse
	 **/
	public function items()
	{
		$data = $this->decode();
		$em = $this->getDoctrine()->getManager();
		$user = $this->getUser()->getId();

		$cat = new Groups();
		$cat->setName($data['name']);
		$cat->setUserId($user);

		$em->persist($cat);
		$em->flush();

		$this->addFlash(
		    'success',
		    'Category ' . $data['name'] . ' added'
		);


		return $this->json(['status' => 'success']);
	}

	/**
	 * @Route("/api/supplier", name="api_supplier")
	 * @param Request $request
	 * @return JsonResponse
	 **/
	public function supplier(Request $request)
	{
		$data = $this->decode();
		$em = $this->getDoctrine()->getManager();
		$user = $this->getUser()->getId();

		$sup = new Supplier();
		$sup->setName($data['name']);
		$sup->setAddress($data['address']);
		$sup->setTelephone($data['telephone'] ?? '');
		$sup->setEmail($data['email'] ?? '');
		$sup->setUserId($user);

		$em->persist($sup);
		$em->flush();


		return $this->json(['status' => 'success']);
	}
}
