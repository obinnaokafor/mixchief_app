<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

/**
 * Base class to create fixtures
 */
abstract class BaseFixture extends Fixture
{
	
	public function load(ObjectManager $manager)
	{
		$this->manager = $manager;

		$this->faker = Factory::create();
		$this->loadData($manager);
	}

	protected function createMany(int $count, string $groupName, callable $factory)
	{
	    for ($i = 0; $i < $count; $i++) {
	        $entity = $factory($i);
	        if (null === $entity) {
	            throw new \LogicException('Did you forget to return the entity object from your callback to BaseFixture::createMany()?');
	        }
	        $this->manager->persist($entity);
	        // store for usage later as groupName_#COUNT#
	        $this->addReference(sprintf('%s_%d', $groupName, $i), $entity);
	    }
	}
}