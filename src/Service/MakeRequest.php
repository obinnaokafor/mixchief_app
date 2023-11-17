<?php

namespace App\Service;

/**
* Send curl requests
*/
class MakeRequest
{
    private $ch;

	public function __construct($url, $headers = [], $extra = [])
	{
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt_array($this->ch, $extra);
        return $this;
	}

    public function get()
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $response = curl_exec($this->ch);
        $err = curl_error($this->ch);

        curl_close($this->ch);
        if ($err) {
            return false;
        }

        return $response;
    }

    public function post($data = [])
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($this->ch);
        $err = curl_error($this->ch);

        curl_close($this->ch);
        if ($err) {
            return false;
        }

        return $response;
    }
}