<?php

namespace App\Service;

use App\Entity\Orders;
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
	 * Payment url
	 */
	const PAYMENT_URL = 'https://api.paystack.co/transaction/initialize';

	/**
	 * Verify url
	 */
	const VERIFY_URL = 'https://api.paystack.co/transaction/verify/';

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

	private $publicKey;

	private $secretKey;

	/**
	 * Instantiate payment helpers
	 * @param LoggerInterface $logger [description]
	 */
	public function __construct($publicKey, $secretKey, LoggerInterface $logger)
	{
		$this->logger = $logger;
		$this->publicKey = $publicKey;
		$this->secretKey = $secretKey;
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
	 * Order payment
	 **/
	public function orderPayment(Orders $order, $delivery = 0)
	{
		$fields = [
            'email' => $order->getCustomer()->getEmail(),
            'amount' => (($order->getAmount()+$delivery) * 100),
        ];

		$request = new MakeRequest(static::PAYMENT_URL, [
			'Authorization: Bearer ' . $this->secretKey,
			'Content-Type: application/json'
		]);

		$response = $request->post(json_encode($fields));

		if (!$response) {
			$this->logger->error(
				'Payment request failed',
				[
					'customer' => $order->getCustomer()->getEmail(),
					'amount' => $order->getAmount()
				]
			);
			return false;
		}

		$response = json_decode($response, true);

		if (!$response['status']) {
			$this->logger->error(
				$response['message'],
				[
					'customer' => $order->getCustomer()->getEmail(),
					'amount' => $order->getAmount()
				]
			);
			return false;
		}

		$this->logger->info(
			$response['data']['authorization_url'],
			[
				'customer' => $order->getCustomer()->getEmail(),
				'amount' => $order->getAmount(),
				'reference' => $response['data']['reference']
			]
		);

		return array(
			'url' => $response['data']['authorization_url'],
			'reference' => $response['data']['reference']
		);
	}

	/**
	 * Verify Transaction
	 * @param string $ref
	 * @return bool
	 **/
	public function verifyPayment($ref)
	{
		$request = new MakeRequest(static::VERIFY_URL . $ref, [
			'Authorization: Bearer ' . $this->secretKey,
			'Content-Type: application/json'
		]);

		$response = $request->get();

		if (!$response) {
			return false;
		}

		$response = json_decode($response);

		if (!$response->status) {
			return false;
		}

		return $response->data;
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
