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
     * @var string
     */
    protected $api = "https://app.sendloop.com/api/v4/";

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

        $this->apiKey = $apiKey;
    }

    /**
     * @param string|array $emailAddress Recipient email address or an array (name, emailAddress)
     * @param Message $message Message object
     * @param array $customArgs Custom arguments as an associated array
     * @param array $mergeVars Merge variables as an associated array
     * @param array $options Options as an associated array
     * @return string
     * @throws Exception
     * @throws HTTPException
     */
    public function send($emailAddress, Message $message, $customArgs = array(), $mergeVars = array(), $options = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 40);
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Sendloop-PHP/1.2.0");
        curl_setopt($ch, CURLOPT_URL, $this->api . "mta.json");
        curl_setopt($ch, CURLOPT_POST, true);

        list($fromName, $fromEmail) = $message->getFrom();
        list($replyToName, $replyToEmail) = $message->getReplyTo();

        if (empty($replyToEmail) === true) {
            $replyToEmail = $fromEmail;
            $replyToName = $fromName;
        }

        if (is_array($emailAddress) && count($emailAddress) === 2) {
            $toName = $emailAddress[0];
            $toEmail = $emailAddress[1];
        } else {
            $toEmail = $emailAddress;
            $toName = "";
        }

        $options["TrackOpens"] = isset($options["TrackOpens"]) && (bool)$options["TrackOpens"] ? "true" : "false";
        $options["TrackClicks"] = isset($options["TrackClicks"]) && (bool)$options["TrackClicks"] ? "true" : "false";
        $options["TrackECommerce"] = isset($options["TrackECommerce"]) && (bool)$options["TrackECommerce"] ? "true" : "false";
        $options["TrackGA"] = isset($options["TrackGA"]) && (bool)$options["TrackGA"] ? "true" : "false";
        $options["Tags"] = isset($options["Tags"]) && is_array($options["Tags"]) ? $options["Tags"] : array();
		$options["EmailID"] = isset($options["EmailID"]) && ((int)$options["EmailID"] > 0) ? $options["EmailID"] : 0;

        $params = http_build_query(array(
            "From" => $fromEmail,
            "FromName" => $fromName,
            "To" => $toEmail,
            "ToName" => $toName,
            "ReplyTo" => $replyToEmail,
            "ReplyToName" => $replyToName,
            "Subject" => $message->getSubject(),
            "TextBody" => $message->getTextContent(),
            "HTMLBody" => $message->getHTMLContent(),
            "MergeVars" => json_encode($mergeVars, JSON_FORCE_OBJECT),
            "CustomArgs" => json_encode($customArgs, JSON_FORCE_OBJECT),
            "TrackOpens" => $options["TrackOpens"],
            "TrackClicks" => $options["TrackClicks"],
            "TrackECommerce" => $options["TrackECommerce"],
            "TrackGA" => $options["TrackGA"],
            "Tags" => json_encode($options["Tags"]),
            "EmailID" => $options["EmailID"]
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

        return $responseDecoded->MessageID;
    }

    /**
     * @param string $messageStatusID
     * @return \stdClass
     * @throws Exception
     * @throws HTTPException
     */
    public function status($messageStatusID = "")
    {
        if (empty($messageStatusID)) {
            $params = "";
        } else {
            $params = "?" . http_build_query(array(
                "id" => $messageStatusID,
            ));
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 40);
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Sendloop-PHP/1.2.0");
        curl_setopt($ch, CURLOPT_URL, $this->api . "mtamessages.json" . $params);

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

}
