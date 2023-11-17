<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;
use Yabacon\Paystack\Event;
use Psr\Log\LoggerInterface;
use App\Service\PaystackHelper;
use App\Entity\Transaction;
use App\Entity\Subscription;
use App\Entity\Users;

class BillingController extends AbstractController
{
	/**
	 * Logger
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Paystack Secret
	 * @var string
	 */
	private $paystack_secret;

	/**
	 * Helper class
	 * @var PaystackHelper
	 */
	private $helper;

	/**
	 *
	 * @param LoggerInterface $logger
	 * @param PaystackHelper  $helper
	 */
	function __construct(LoggerInterface $logger, PaystackHelper $helper)
	{
		$this->logger = $logger;
		$this->helper = $helper;
		$this->paystack_secret = getenv('PAYSTACK_SECRET');
	}

    /**
     * @Route("/admin/subscribe", name="subscribe")
     *
     * @return void
     * @author
     **/
    public function index()
    {
        $user = $this->getuser();
        $em = $this->getDoctrine()->getManager();
        $subscription = $em->getRepository(Subscription::class)->findOneBy(['user' => $user]);
        $now = new \DateTime('now');
        if ($subscription->getStatus()) {
            $package = 'All-in-One';
            $expiry = $subscription->getExpiry()->format('d-m-Y');
        }else {
            $package = 'Free Trial';
            $extend = $user->getCreated();
            $extend->modify("+14 days");
            $expiry = $now < $extend ? $extend->format('d-m-Y') : 'Expired';
        }

        return $this->render('Default/subscribe.html.twig', ['title' => 'Subscribe', 'subscription' => $package, 'expiry' => $expiry]);

    }

    /**
     * @Route("/admin/pay", name="pay")
     */
    public function pay()
    {

    	$paystack = new Paystack($this->paystack_secret);
    	// $amount = getenv(strtoupper($plan));

    	$user = $this->getUser();
    	$email = $user->getEmail();
        $months = isset($_POST['months']) ? $_POST['months'] : 1;
        $amount = intval($months) == 0 ? getenv('PAYSTACK_STANDARD') : intval($months) * getenv('PAYSTACK_STANDARD');

    	try
    	{
    	  $tranx = $paystack->transaction->initialize([
    	    'amount'=>$amount,
    	    'email'=>$email,
            'metadata' => array(
                "months" => $months,
                "subscription" => "new"
            ),
    	  ]);
    	} catch(ApiException $e){
    	  print_r($e->getResponseObject());
    	  die($e->getMessage());
    	}

    	$url = $tranx->data->authorization_url;
    	$this->logger->info(
    		$tranx->data->reference,
    		array(
    			"user" => $email,
    			"amount" => $amount,
    		)
    	);

    	return $this->redirect($url);
    }

    /**
     * @Route("/payment/charge/handle", name="paystackWebhook")
     * @param Request $request
     * @return Response
     **/
    public function handleHook(Request $request)
    {
    	// $signature = $request->headers->get('HTTP_X_PAYSTACK_SIGNATURE');
    	$event = Event::capture();

    	// var_dump($event);die;

    	$my_keys = [
    	            'live' => $this->paystack_secret
    	          ];
    	$owner = $event->discoverOwner($my_keys);

    	if (!$owner) {
				return new Response('hello no');
    	}

    	$now = new \DateTime('now');

      $evtData = $event->obj->data;

        switch ($event->obj->event) {
            case 'charge.success':

                $em = $this->getDoctrine()->getManager();

                $user = $em->getRepository(Users::class)->findOneBy(['email' => $evtData->customer->email]);

                $authCode = $evtData->authorization->authorization_code;

                $user->setPaystackAuth($authCode);
                $em->persist($user);
                $em->flush();

								// Check if user has a subscripttion
                $userSub = $em->getRepository(Subscription::class)->findOneBy(['user' => $user]);

                if (!$userSub || !$userSub->getStatus()) {
                    $subscription = $this->subscribe($user, $authCode, $evtData->metadata->months);
                }

                break;
            case 'subscription.disable':
                $this->disableSub($evtData);
                break;
            case 'subscription.create':
                $this->subscription($evtData);
                break;
            case 'subscription.enable':
                $this->enableSub($evtData);
                break;

            default:
                break;
        }

    	$this->logger->info(
    		$now->format('d:m:Y H:i:s'),
    		array(
    			"body" => $event->raw
    		)
    	);

    	return new Response('hello');
    }

    /**
     * Enable subscription
     * @param array $data
     * @return bool
     **/
    public function enableSub($data)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(Users::class)->findOneBy(['email' => $data->customer->email]);
        $userSub = $em->getRepository(Subscription::class)->findOneBy(['user' => $user]);

        if ($userSub) {
            $userSub->setStatus(true);
            $userSub->setRenew(true);

            $em->persist($userSub);
            $em->flush();
        }

        return true;
    }

    /**
     * Save Subscription Details
     * @param array $data
     **/
    public function subscription($data)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(Users::class)->findOneBy(['email' => $data->customer->email]);

        // Check if user has an expired/cancelled subscription
        // else save a new subscription
        if ($user) {
            $userSub = $em->getRepository(Subscription::class)->findOneBy(['user' => $user]);
            $sub = $userSub ?: new Subscription();
            $sub->setEmailToken($data->email_token);
            $sub->setCode($data->subscription_code);
            $sub->setAuthcode($data->authorization->authorization_code);
            $sub->setStart(new \DateTime('now'));
            $sub->setExpiry(\DateTime::createFromFormat('Y-m-d\TH:i:s+', $data->next_payment_date));
            $sub->setUser($user);
            $sub->setStatus(true);
            $sub->setRenew(true);

            $em->persist($sub);
            $em->flush();

            return $sub;
        }

				throw new ApiException("Error Processing Request");

    }

    /**
		 * @param Users $user
		 * @param string $auth
     * @param int $duration
     **/
    public function subscribe($user, $auth, $duration = 1)
    {
    	$em = $this->getDoctrine()->getManager();

    	// Get the date the user registered and set the subscription start date
    	// to be 2 weeks from that date (so the user can still enjoy their free trial)
    	// But if the user registered more that a month from today, set subscription start date to today
    	$created = $user->getCreated();
    	$now = new \DateTime('now');
        $created->modify("+14 days");
        $time = $created > $now ? $created : $now;
        $extend = "+" . $duration . " month";
    	$time->modify($extend);
    	// $newTime = $expiry < $now ? $now : $expiry;
    	$start = date("c", $time->getTimestamp());
        // var_dump($start);die;

    	// Check if the user has a paystack customer code i.e user is registered as a customer
    	if (!$user->getPaystackCode()) {
    		if ($result = $this->helper->createCustomer($user)) {
    			$user->setPaystackCode($result['code']);
    			$em->persist($user);
          $em->flush();
    		}
    	}

    	// Subscribe
    	$subtranx = $this->helper->subscription($start, $user, $auth);

    	return $subtranx ? true : false;


    }

    /**
     * Handle disabled subscription
     *
     * @param array $evtData
     **/
    public function disableSub($evtData)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(Users::class)->findOneBy(['email' => $evtData->customer->email]);
        $subscription = $em->getRepository(Subscription::class)->findOneBy(['user' => $user]);
        // $now = new
        if ($subscription) {
            $subscription->setRenew(false);
            $em->persist($subscription);
            $em->flush();

            return true;
        }
    }

    /**
     * Handle Failed transaction
     *
     **/
    public function failedCharge()
    {
			// TODO: Handle failed charge
    }

    /**
     * @Route("/payment/paystack/callback", name="paystackCallback")
     * @param Request $request
     **/
    public function paymentCallback(Request $request)
    {
    	$reference = $request->query->get('reference');

    	$tranx = $this->helper->verify($reference);
        // var_dump($tranx);die;

    	if (!$tranx) {
    		return $this->redirectToRoute('payment', ['status' => false]);
    	}

        $em = $this->getDoctrine()->getManager();

    	// Check if transaction already exists with same reference
        if (!$em->getRepository(Transaction::class)->findOneBy(['reference' => $reference])) {
            $now = new \DateTime('now');

            $user = $em->getRepository(Users::class)->findOneBy(['email' => $tranx->data->customer->email]);
            $transaction = new Transaction();
            $transaction->setCreated($now);
            $transaction->setAmount($tranx->data->amount);
            $transaction->setReference($tranx->data->reference);
            $transaction->setUserId($user->getId());
            $transaction->setLastFour($tranx->data->authorization->last4);
            $transaction->setPaystackAuth($tranx->data->authorization->authorization_code);
            $em->persist($transaction);
            $em->flush();
        }

        $this->addFlash(
            'success',
            'Subscribed Successfully.'
        );

    	return $this->redirectToRoute('admin');

    }

    /**
     * @Route("/payment/complete/{success}", name="payment")
     * Complete payment
     *
     * @return Response
     * @param string $status status of the transaction
     **/
    public function payment($status)
    {
    	$message = $status ? 'Payment Successful' : 'Payment failed';

    	$this->addFlash(
            $status ? 'success' : 'danger',
            $message
        );

    	return $this->render('billing/payment.html.twig', ['success' => $status]);
    }

}
