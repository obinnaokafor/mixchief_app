<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ItemPart
 *
 * @ORM\Table(name="item_part")
 * @ORM\Entity(repositoryClass="App\Repository\ItemPartRepository")
 */
class ItemPart
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
     * @ORM\Column(name="item_id", type="integer")
     */
    private $itemId;

    /**
     * @var int
     *
     * @ORM\Column(name="stock_id", type="integer")
     */
    private $stockId;

    /**
     * @var float
     *
     * @ORM\Column(name="portion", type="float")
     */
    private $portion;

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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return ItemPart
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set stockId
     *
     * @param integer $stockId
     *
     * @return ItemPart
     */
    public function setStockId($stockId)
    {
        $this->stockId = $stockId;

        return $this;
    }

    /**
     * Get stockId
     *
     * @return int
     */
    public function getStockId()
    {
        return $this->stockId;
    }

    /**
     * Set portion
     *
     * @param float $portion
     *
     * @return ItemPart
     */
    public function setPortion($portion)
    {
        $this->portion = $portion;

        return $this;
    }

    /**
     * Get portion
     *
     * @return float
     */
    public function getPortion()
    {
        return $this->portion;
    }
}

