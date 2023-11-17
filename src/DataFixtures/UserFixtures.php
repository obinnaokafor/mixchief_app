<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\Users;

class UserFixtures extends BaseFixture
{
	private $passwordEncoder;

	public function __construct(UserPasswordEncoderInterface $passwordEncoder)
	{
		$this->passwordEncoder = $passwordEncoder;
	}

    protected function loadData(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);
        $this->createMany(10, 'main_user', function($i){
        	$user = new Users();
        	$user->setEmail(sprintf('spacebar%d@example.com', $i));
        	$user->setTelephone(sprintf('0818087167%d', $i));
        	$user->setPassword($this->passwordEncoder->encodePassword(
        		$user,
        		'nasdaq22'
        	));
        	$user->setCountry($this->faker->country);
        	$user->setEnabled(true);

        	return $user;
        });

        $manager->flush();
    }
}
