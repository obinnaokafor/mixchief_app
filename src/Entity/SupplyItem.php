<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
// use App\Entity\Supply;

/**
 * SupplyItem
 *
 * @ORM\Table(name="supply_item")
 * @ORM\Entity(repositoryClass="App\Repository\SupplyItemRepository")
 */
class SupplyItem
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="stock", type="integer")
     * @ORM\ManyToOne(targetEntity="App\Entity\Stock")
     */
    private $stock;

    /**
     * @var int
     *
     * @ORM\Column(name="supply_id", type="integer")
     * @ORM\ManyToOne(targetEntity="App\Entity\Supply", inversedBy="supplyItems")
     */
    private $supplyId;

    /**
     * @var int
     *
     * @ORM\Column(name="quantity", type="integer", nullable=true)
     */
    private $quantity;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", nullable=true)
     */
    private $amount;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * Set userId
     *
     * @param int $userId
     *
     * @return Groups
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set stock
     *
     * @param integer $stock
     *
     * @return SupplyItem
     */
    public function setStock($stock)
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * Get stock
     *
     * @return int
     */
    public function getStock()
    {
        return $this->stock;
    }

    /**
     * Set supplyId
     *
     * @param integer $supplyId
     *
     * @return SupplyItem
     */
    public function setSupplyId($supplyId)
    {
        $this->supplyId = $supplyId;

        return $this;
    }

    /**
     * Get supplyId
     *
     * @return int
     */
    public function getSupplyId()
    {
        return $this->supplyId;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return SupplyItem
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return SupplyItem
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }
}
