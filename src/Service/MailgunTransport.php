<?php

namespace App\Service;

use Mailgun\Mailgun;

/**
* Mailgun transport class to handle transactional emails
*/
class MailgunTransport
{
	private $api_key;

	private $domain;

	private $mailgun;

	public function __construct($apiKey, $domain)
	{
		$this->api_key = $apiKey;
		$this->domain = $domain;

		if ($this->mailgun === null) {
			$this->mailgun = Mailgun::create($this->api_key);
		}

		return $this;
	}


	public function send($to, $subject, $message, $plain = null, $company = null)
	{
		$comp = $company ?: 'Amply';
		$result = $this->mailgun->messages()->send($this->domain, array(
			'from' => $comp . ' <no-reply-register@amply.com.ng>',
			'to' => $to,
			'subject' => $subject,
			'html' => $message
		));
		return $result ? true : false;
	}
}