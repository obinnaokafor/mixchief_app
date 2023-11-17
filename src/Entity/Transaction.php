<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TransactionRepository")
 */
class Transaction
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="integer")
     */
    private $user_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $reference;

    /**
     * @ORM\Column(name="paystack_auth", type="string", length=255, nullable=true)
     */
    private $paystack_auth;

    /**
     * Last four digits of card
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var integer
     **/
    private $last_four;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getLastFour(): ?int
    {
        return $this->last_four;
    }

    public function setLastFour(int $last_four): self
    {
        $this->last_four = $last_four;

        return $this;
    }

    /**
     * @return String
     */   
    public function getPaystackAuth(): ?string
    {
        return $this->paystack_auth;
    }

     /**
     * @param String $paystack_auth
     * @return self
     */   
    public function setPaystackAuth(string $paystack_auth): self
    {
        $this->paystack_auth = $paystack_auth;

        return $this;
    }
}
