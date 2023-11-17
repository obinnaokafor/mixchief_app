<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Subscription;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UsersRepository")
 * @UniqueEntity(fields={"email"},message="Email already in use!")
 * @UniqueEntity(fields={"telephone"},message="Telephone number already in use!")
 */
class Users implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, unique=false, nullable=true)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="lname", type="string", length=255, nullable=true)
     * @var string
     */
    private $lname;

    /**
     * @ORM\Column(type="json")
     * @var array
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $verification;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $reset;

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=true)
     * @Assert\NotBlank()
     * @var string
     */
    private $telephone;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $enabled = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $state;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @var string
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $logo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=true)
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified", type="datetime", nullable=true)
     */
    private $modified;

    /**
     * @ORM\OneToOne(targetEntity="Subscription", mappedBy="user")
     * @var Subscription
     */
    private $subscription;

    /**
     * @ORM\Column(name="paystack_code", type="string", length=255, nullable=true)
     * @var string
     * Paystack Customer Code
     */
    private $paystack_code;

    /**
     * @ORM\Column(name="paystack_auth", type="string", length=255, nullable=true)
     * @var string
     */
    private $paystack_auth;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $apiKey;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLName(): ?string
    {
        return $this->lname;
    }

    /**
     * @param string $lname
     * @return self
     */
    public function setLName(string $lname): self
    {
        $this->lname = $lname;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

     /**
     * @param string $address
     * @return self
     */
    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

     /**
     * @param string $state
     * @return self
     */
    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

     /**
     * @param string $company
     * @return self
     */
    public function setCompany(string $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }

     /**
     * @param string $logo
     * @return self
     */
    public function setLogo($logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     * @return string
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     * @return array
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param  array $roles
     * @return self
     */
    public function setRoles(array $roles): self
    {
        $this->roles = count($roles) ? $roles : ['ROLE_USER'];

        return $this;
    }

    /**
     * @see UserInterface
     * @return string
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }


    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     *
     * @return string|null
     */
    public function getVerification(): ?string
    {
        return $this->verification;
    }

    /**
     *
     * @param  string|null $verification
     * @return self
     */
    public function setVerification(?string $verification): self
    {
        $this->verification = $verification;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReset(): ?string
    {
        return $this->reset;
    }

    /**
     *
     * @param  string|null $reset
     * @return self
     */
    public function setReset(?string $reset): self
    {
        $this->reset = $reset;

        return $this;
    }

    /**
     *
     * @return string|null
     */
    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    /**
     *
     * @param  string $telephone
     * @return self
     */
    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     *
     * @return bool|null
     */
    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     *
     * @param  bool $enabled
     * @return self
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     *
     * @return \DateTimeInterface|null
     */
    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    /**
     * @param  \DateTimeInterface $created
     * @return self
     */
    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     *
     * @return \DateTimeInterface|null
     */
    public function getModified(): ?\DateTimeInterface
    {
        return $this->modified;
    }

    /**
     *
     * @param  \DateTimeInterface $modified
     * @return self
     */
    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     *
     * @return Subscription|null
     */
    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    /**
     *
     * @param  Subscription $subscription
     * @return self
     */
    public function setSubscription(Subscription $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaystackCode(): ?string
    {
        return $this->paystack_code;
    }

     /**
     * @param string $paystack_code
     * @return self
     */
    public function setPaystackCode(string $paystack_code): self
    {
        $this->paystack_code = $paystack_code;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaystackAuth(): ?string
    {
        return $this->paystack_auth;
    }

     /**
     * @param string $paystack_auth
     * @return self
     */
    public function setPaystackAuth(string $paystack_auth): self
    {
        $this->paystack_auth = $paystack_auth;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }
}
