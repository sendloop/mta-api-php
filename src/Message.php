<?php

namespace Sendloop\MTA;


use Sendloop\MTA\Exception\InvalidArgumentException;

class Message
{
    /**
     * @var string From name of the message
     */
    protected $fromName = "";

    /**
     * @var string From email address of the message
     */
    protected $fromEmail = "";

    /**
     * @var string|null Reply-to name of the message
     */
    protected $replyToName = "";

    /**
     * @var string|null Reply-to email address of the message
     */
    protected $replyToEmail = "";

    /**
     * @var string Subject of the message
     */
    protected $subject = "";

    /**
     * @var string Text content of the message
     */
    protected $text = "";

    /**
     * @var string HTML content of the message
     */
    protected $html = "";

    /**
     * Message constructor.
     */
    public function __construct()
    {

    }

    /**
     * Sets from name and email address of the message
     * @param string $fromName From name
     * @param string $fromEmail From email address
     * @throws InvalidArgumentException
     */
    public function setFrom($fromName = null, $fromEmail = null)
    {
        if (empty($fromName) || empty($fromEmail)) {
            throw new InvalidArgumentException("fromName and fromEmail can't be empty");
        }
        $this->fromName = $fromName;
        $this->fromEmail = $fromEmail;
    }

    /**
     * Returns an array with from name and from email
     * @return array
     */
    public function getFrom()
    {
        return array($this->fromName, $this->fromEmail);
    }

    /**
     * Sets reply-to name and email address of the message
     * @param null $replyToName
     * @param null $replyToEmail
     */
    public function setReplyTo($replyToName = null, $replyToEmail = null)
    {
        $this->replyToName = $replyToName;
        $this->replyToEmail = $replyToEmail;
    }

    /**
     * Returns an array with reply-to name and reply-to email
     * @return array
     */
    public function getReplyTo()
    {
        return array($this->replyToName, $this->replyToEmail);
    }

    /**
     * Sets message's plain text content
     * @param string $text
     */
    public function setTextContent($text = "")
    {
        $this->text = $text;
    }

    /**
     * Returns message's plain text content
     * @return string
     */
    public function getTextContent()
    {
        return $this->text;
    }

    /**
     * Sets message's HTML content
     * @param string $html
     */
    public function setHTMLContent($html = "")
    {
        $this->html = $html;
    }

    /**
     * Returns message's HTML content
     * @return string
     */
    public function getHTMLContent()
    {
        return $this->html;
    }

    /**
     * Sets message's subject
     * @param string $subject
     */
    public function setSubject($subject = "")
    {
        $this->subject = $subject;
    }

    /**
     * Returns message's subject
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }
}