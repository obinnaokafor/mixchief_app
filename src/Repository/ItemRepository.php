<?php

namespace App\Repository;

/**
 * ItemRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ItemRepository extends \Doctrine\ORM\EntityRepository
{
	// Select all the items and categories for the logged in user 
	// to be used in the point of sale
	public function findPointOfSale($user)
	{
	    return $this->getEntityManager()
	        ->createQuery(
	            'SELECT i.id as item_id, i.name as item_name, i.sellingPrice as item_price, i.img as item_image, g.name as group_name from App\Entity\Item i, App\Entity\Groups g where g.id = i.cId AND i.userId = :user AND i.deleted is NULL'
	        )->setParameter('user', $user)
	        ->getResult();
	}

	public function findPointOfSaleDrinks()
	{
	    return $this->getEntityManager()
	        ->createQuery(
	            'SELECT c.id as cat_id, c.name as cat_name, i.id as item_id, i.name as item_name, i.sellingPrice as item_price, i.img as item_image, g.name as group_name from App\Entity\Groups g, App\Entity\Item i, App\Entity\Groups g where c.id = i.cId and c.gId = g.id and g.name = :name AND i.deleted is NULL'
	        )->setParameter('name', 'drinks')
	        ->getResult();
	}

	// 
	public function findAllCat($user)
	{
	    return $this->getEntityManager()
	        ->createQuery(
	            'SELECT i.id as id, i.name as name, i.sellingPrice as price, g.name as catname FROM App\Entity\Groups g, App\Entity\Item i WHERE g.id = i.cId AND i.userId = :user AND i.deleted is NULL'
	        )->setParameter('user', $user)
	        ->getResult();
	}

	public function findCat($id)
	{
	    return $this->getEntityManager()
	        ->createQuery(
	            'SELECT i.id as id, i.name as name, i.sellingPrice as price, g.name as category FROM App\Entity\Groups g, App\Entity\Item i WHERE g.id = i.cId AND i.id = :id AND i.deleted is NULL'
	        )->setParameter('id', $id)
	        ->getResult();
	}

	public function searchByName($key)
	{
		$query = $this->createQueryBuilder('p')
		                ->where('p.name LIKE :names')
		                ->andWhere('p.deleted is NULL')
		                ->setParameter('names', '%' . $key . '%')
		                ->getQuery();

		return $query->getArrayResult();
	}

	public function deleteByCategory($id, $user)
	{
	    return $this->getEntityManager()
	        ->createQuery(
	            'UPDATE App\Entity\Item i SET i.deleted = 1 WHERE i.cId = :id AND i.userId = :user'
	        )->setParameter('id', $id)
	        ->setParameter('user', $user)
	        ->getResult();
	}

	
}