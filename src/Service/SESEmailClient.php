<?php

// an SES email client class to use the AWS SDK to send emails

namespace App\Service;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

class SESEmailClient {
    private $sesClient;

    public function __construct($awsKey, $awsSecret, $region) {
        $this->sesClient = new SesClient([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $awsKey,
                'secret' => $awsSecret
            ]
        ]);
    }

    public function sendEmail($to, $subject, $message) {
        $params = [
            'Destination' => [
                'ToAddresses' => [$to],
            ],
            'Message' => [
                'Body' => [
                    'Html' => [
                        'Charset' => 'UTF-8',
                        'Data' => $message,
                    ],
                ],
                'Subject' => [
                    'Charset' => 'UTF-8',
                    'Data' => $subject,
                ],
            ],
            'Source' => 'hello@themixchief.com',
        ];

        try {
            $result = $this->sesClient->sendEmail($params);
            $messageId = $result['MessageId'];
            return "Email sent! Message ID: $messageId\n";
        } catch (AwsException $e) {
            // echo "Error sending email: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
