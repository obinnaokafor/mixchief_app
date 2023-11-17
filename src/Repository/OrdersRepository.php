<?php

namespace App\Repository;

/**
 * OrdersRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrdersRepository extends \Doctrine\ORM\EntityRepository
{
	public function findPendingOrders($user)
	{
	    return $this->getEntityManager()
	        ->createQuery(
	            'SELECT oi.id as id, i.id as item_id, i.name as item_name, oi.price as item_price, oi.discount as discount, oi.quantity as quantity, o.id as order_id, o.date as order_date, o.status as status, o.amount as amount from App\Entity\Orders o, App\Entity\Item i, App\Entity\OrderItem oi where i.id = oi.itemId and o.id = oi.orderId and o.status = :pending and o.userId = :user'
	        )->setParameter('pending', 'Pending')
	        ->setParameter('user', $user)
	        ->getResult();
	}

	public function findPendingOrder($id, $user)
	{
	    return $this->getEntityManager()
	        ->createQuery(
	            'SELECT i.id as id, i.name as name, g.name as category, oi.price as price, oi.discount as discount, oi.quantity as quantity, o.id as orderid, o.status as status from App\Entity\Orders o, App\Entity\Item i, App\Entity\OrderItem oi, App\Entity\Groups g where i.id = oi.itemId and o.id = oi.orderId and o.status = :pending and o.userId = :user and o.id = :id and g.id = i.cId'
	        )->setParameter('pending', 'Pending')
	        ->setParameter('user', $user)
	        ->setParameter('id', $id)
	        ->getResult();
	}

	/**
	 * Delete Pending Order
	 *
	 **/
	public function deletePendingOrder($id, $user)
	{
		return $this->getEntityManager()
		    ->createQuery(
		        'DELETE FROM App\Entity\Orders o WHERE o.id = :id AND o.userId = :user'
		    )->setParameter('id', $id)
		    ->setParameter('user', $user)
		    ->getResult();
	}

	public function findOrder($id, $user)
	{
	    return $this->getEntityManager()
	        ->createQuery(
	            'SELECT o.id as id, o.date as date, o.amount as amount, o.payment as payment, o.status as status, s.name as waiter, u.name as cashier from App\Entity\Orders o, App\Entity\Users u, App\Entity\Staff s where o.staffId = s.id and o.cashier = u.id and o.id = :id AND o.userId = :user'
	        )->setParameter('id', $id)
	        ->setParameter('user', $user)
	        ->getResult();
	}

	public function findLastOrder($user)
	{
		return $this->getEntityManager()
	        ->createQuery(
	            'SELECT o.id as id from App\Entity\Orders o WHERE o.userId = :user ORDER BY o.date DESC'
	        )->setParameter('user', $user)
	        ->setMaxResults(1)
	        ->getResult();
	}

	/**
	 * Retrieve the total of orders placed today
	 *
	 **/
	public function salesTotal($user, $start, $now)
	{
		// $start = new \DateTime(date('d-m-Y'));
		// $now = new \DateTime('now');
		return $this->getEntityManager()
					->createQuery(
						'SELECT SUM(o.amount) FROM App\Entity\Orders o WHERE o.date BETWEEN :start AND :now AND o.userId = :user AND o.status = :status'
					)->setParameter('start', $start)
					->setParameter('now', $now)
					->setParameter('user', $user)
					->setParameter('status', 'Completed')
					->getSingleScalarResult();
	}

	/**
	 * Retrieve daily order totals for current month
	 *
	 **/
	public function salesPeriod($user, $from, $to)
	{
		// $month = date('Y-m-01 H:i:s');
		// $now = (new \DateTime('now'))->format('Y-m-d H:i:s');
		$conn = $this->getEntityManager()
            			->getConnection();

        $sql = "SELECT CAST(date AS DATE) as dates, SUM(amount) as amount FROM `orders` WHERE user_id = ? AND date BETWEEN ? AND ? GROUP BY CAST(date AS DATE)";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $user);
        $stmt->bindValue(2, $from);
        $stmt->bindValue(3, $to);
        $stmt->executeQuery();
        return $stmt;
	}


	public function findPayment($dates, $user)
	{
		return $this->getEntityManager()
	        ->createQuery(
	            'SELECT o.payment as payment, sum(o.amount) as amount from App\Entity\Orders o WHERE o.date > :dates AND o.userId = :user GROUP BY o.payment'
	        )->setParameter('dates', $dates)
	        ->setParameter('user', $user)
	        ->getResult();
	}

	public function printReport($dates)
	{
		$date = date('Y-m-d');
	    return $this->getEntityManager()
	        ->createQuery(
	            'SELECT g.name as group_name, i.name as item_name, i.id as item_id, sum(oi.quantity) as quantity, sum((oi.price*oi.quantity)) as item_price, sum(oi.discount) as discount from App\Entity\Item i, App\Entity\Groups g, App\Entity\Orders o, App\Entity\OrderItem oi where oi.itemId = i.id and oi.orderId = o.id and i.cId = g.id and o.date > :dates group by g.name, i.name, i.id'
	        )->setParameter('dates', '2017-08-31')
	        ->getResult();
	}

	// public function findCancelled($dates)
	// {
	//     return $this->getEntityManager()
	//         ->createQuery(
	//             'SELECT (:cancelled) as group_name, (:void) as cat_name, i.name as item_name, sum(c.price) as item_price, (:price) as discount, sum(c.quantity) as quantity from App\Entity\Item i, App\Entity\Cancelled c, App\Entity\Orders o where i.id = c.itemId and o.id = c.orderId and c.date > :dates group by group_name, cat_name, i.name'
	//         )->setParameter('dates', '2017-08-31')
	//         ->setParameter('cancelled', 'CANCELLED')
	//         ->setParameter('void', 'VOID')
	//         ->setParameter('price', 0)
	//         ->getResult();
	// }
}
