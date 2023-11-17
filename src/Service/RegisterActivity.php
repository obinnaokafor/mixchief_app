<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\Activity;

/**
 * Register an activity
 */
class RegisterActivity
{
	private $entityManager;
	private $token;

	public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $token)
	{
		$this->entityManager = $entityManager;
		$this->token = $token;
	}

	/**
	 * Register an activity
	 *
	 * @param string $entity the name of the entity
	 * @param int $id the id of item
	 * @param string $message the action taken
	 **/
	public function register($entity, $id, $message)
	{
		if (null === $userToken = $this->token->getToken()) {
			return;
		}

		$user = $userToken->getUser()->getId();
		$item = $this->entityManager->getRepository("App\Entity\\$entity")->findOneBy(['id' => $id, 'userId' => $user]);

		$activity = new Activity();
		$activity->setDate(new \DateTime('now'));
		$activity->setAction($message);
		$activity->setEntity($entity);
		$activity->setUserId($user);
		$activity->setEntityId($id);

		$this->entityManager->persist($activity);
		$this->entityManager->flush();

		return true;
	}
}