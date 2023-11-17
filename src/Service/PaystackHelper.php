<?php

namespace App\Service;

use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;
// use Yabacon\Paystack\Event;
use Psr\Log\LoggerInterface;

/**
 * Register an activity
 */
class PaystackHelper
{
	/**
	 * Logger
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Paystack instance
	 * @var Paystack
	 */
	private $paystack;

	/**
	 * Standard plan
	 * @var string
	 */
	private $standard_plan;

	/**
	 * Test Plan
	 * @var string
	 */
	private $test_plan;

	/**
	 * Instantiate payment helpers
	 * @param LoggerInterface $logger [description]
	 */
	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
		$this->paystack = new Paystack(getenv('PAYSTACK_SECRET'));
		$this->standard_plan = getenv('STANDARD_PLAN');
		$this->test_plan = getenv('TEST_PLAN');
	}

	/**
	 * Create New Customer
	 * @param \App\Entity\Users $user
	 **/
	public function createCustomer($user)
	{
		try {
			$customer = $this->paystack->customer->create(
						  [
			                'first_name'=>$user->getName(),
			                'last_name'=>$user->getLName(),
			                'email'=>$user->getEmail(),
			                'phone'=>$user->getTelephone()
			              ]
			          	);
		} catch (ApiException $e) {
			return NULL;
		}

		$code = $customer->data->customer_code;

		$this->logger->info(
			$code,
			array(
				"integration" => $customer->data->integration,
				"id" => $customer->data->id
			)
		);

		return array('id' => $customer->data->id, 'code' => $code);
	}

	/**
	 * Verify Transaction
	 * @param string $ref
	 * @return bool
	 **/
	public function verify($ref)
	{
    	try
	    {
	      $tranx = $this->paystack->transaction->verify([
	        'reference'=>$ref,
	      ]);
	    } catch(ApiException $e){
	      $this->logger->info(
	      	$e->getMessage()
	      );
	      return false;
	    }

	    // return $this->json($tranx);

	    return $tranx;
	}

	/**
	 * Start a subscription
	 *
	 * @param \DateTime $start Subscription start date
	 * @param \App\Entity\Users $user
	 * @param string $code
	 **/
	public function subscription($start, $user, $code)
	{
		try {

			$sub = $this->paystack->subscription->create([
                'plan'=>$this->standard_plan,
                'customer'=>$user->getPaystackCode(),
                'authorization'=>$code,
                'start_date' => $start
              ]);
		} catch (ApiException $e) {
			$this->logger->error(
				$e->getMessage(),
				['type' => 'subscription', 'customer' => $user->getEmail(), 'code' => $code]
			);
			return false;
		}

		return $sub;
	}
}
