<?php

namespace Sendloop\MTA;


use Sendloop\MTA\Exception\Exception;
use Sendloop\MTA\Exception\InvalidAPIKeyException;
use Sendloop\MTA\Exception\HTTPException;

class Mailer
{
    /**
     * @var string
     */
    protected $apiKey = "";

    /**
     * @var cURL resource
     */
    protected $curl;

    /**
     * Mailer constructor.
     * @param null $apiKey
     * @throws InvalidAPIKeyException
     */
    public function __construct($apiKey = null)
    {
        if (is_null($apiKey)) {
            throw new InvalidAPIKeyException();
        }

        $this->initCurl();
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $emailAddress Recipient email address
     * @param Message $message Message object
     * @param array $customArgs Custom arguments as an associated array
     * @return \stdClass
     * @throws Exception
     * @throws HTTPException
     */
    public function send($emailAddress, Message $message, $customArgs = array())
    {
        $ch = $this->curl;

        list($fromName, $fromEmail) = $message->getFrom();
        list($replyToName, $replyToEmail) = $message->getReplyTo();

        if (empty($replyToEmail) === true) {
            $replyToEmail = $fromEmail;
            $replyToName = $fromName;
        }

        $params = http_build_query(array(
            "From" => $fromEmail,
            "FromName" => $fromName,
            "To" => $emailAddress,
            "ReplyTo" => $replyToEmail,
            "ReplyToName" => $replyToName,
            "Subject" => $message->getSubject(),
            "TextBody" => $message->getTextContent(),
            "HTMLBody" => $message->getHTMLContent(),
            "CustomArgs" => json_encode($customArgs, JSON_FORCE_OBJECT)
        ));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getRequestHeaders());

        $response = curl_exec($ch);
        if (curl_error($ch)) {
            throw new HTTPException("API call to Sendloop MTA failed: " . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode === 401) {
            throw new InvalidAPIKeyException();
        }

        $responseDecoded = json_decode($response);
        if ($responseDecoded === null) {
            throw new Exception("We couldn't decode the JSON response from Sendloop API: " . $response);
        }

        $flooredHttpCode = floor($responseDecoded->HttpCode / 100);
        if ($flooredHttpCode >= 4) {
            throw new Exception(isset($responseDecoded->Status) ? $responseDecoded->Status : "");
        }

        return $responseDecoded;
    }

    /**
     * Returns HTTP request headers for the request
     * @return array
     */
    protected function getRequestHeaders()
    {
        return array(
            "Authorization: Basic " . base64_encode("{$this->apiKey}:X")
        );
    }

    /**
     * Initializes CURL object
     */
    protected function initCurl()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 40);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_USERAGENT, "Sendloop-PHP/1.0.0");
        curl_setopt($this->curl, CURLOPT_URL, "https://sendloop.com/api/v4/mta.json");
        curl_setopt($this->curl, CURLOPT_POST, true);
    }
}