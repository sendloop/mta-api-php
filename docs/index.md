# Welcome to Sendloop MTA PHP SDK Documentation

Sendloop MTA is a transaction email delivery gateway and this PHP SDK lets you to use API easily.

## Quick Start

First, let's install the library with composer:

    composer require sendloop/mta-api-php

Initialize the mailer with your API key:

    require_once "vendor/autoload.php"; // Include composer autoloader
    $mailer = new \Sendloop\MTA\Mailer("YOUR-API-KEY-HERE");

> You can grab your API key from Settings > API Settings page on your Sendloop account.

After initializing the mailer, let's create a message:

    $message = new \Sendloop\MTA\Message();
    $message->setFrom("Sendloop Developers", "hello@sendloop.com");
    $message->setReplyTo("Sendloop", "hello@sendloop.com");
    $message->setSubject("Quick start guide to Sendloop MTA PHP SDK");
    $message->setTextContent("...");
    $message->setHTMLContent("...");

and send the message to our recipient with mailer:

    $mailer->send("test@recipient.com", $message, array("custom_arg_1" => "my_test_value"));