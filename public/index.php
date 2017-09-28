<?php
declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

// Configure timezone.
date_default_timezone_set('America/New_York');

/** @var ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';

// Create a logger.
$logger = new Logger(basename(__FILE__));
$stream = sprintf(__DIR__.'/../%s.log', $logger->getName());
$logger->pushHandler(new StreamHandler($stream));

// Configure a global exception handler.
set_exception_handler('exception_handler');

// Allow POST and OPTIONS HTTP methods and any HTTP header.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header('Access-Control-Allow-Methods: OPTIONS, POST');
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header('Access-Control-Allow-Headers: '.$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
    }
    respond(204);
}

// Allow only POST HTTP method.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, 'Request method not allowed.');
}

// Get the request body.
$json = file_get_contents('php://input');
if ($json === '') {
    respond(422, 'Request does not contain any data.');
}

// Decode the request body.
$data = json_decode($json, true);
if ($data === null) {
    respond(422, 'Request does not contain valid JSON.');
}
$logger->debug(json_encode($json));

// Validate the data?
// ¯\_(ツ)_/¯

// Configure templating.
$twig = new Twig_Environment(new Twig_Loader_Filesystem([__DIR__.'/../templates']));

// Render the templates.
$context = ['data' => $data, 'timestamp' => new DateTime()];
$text = $twig->render('email.txt.twig', $context);
$html = $twig->render('email.html.twig', $context);

// Create an email message.
$message = new Swift_Message();
$message->setFrom('JWS@gojws.com', 'JWS Website Form')
        ->setBcc('tjmaco@gmail.com', 'TJ Maco')
        ->setSubject('JWS Website Form')
        ->setBody($text, 'text/plain')
        ->addPart($html, 'text/html');

$transport = new Swift_SendmailTransport();

// Create the mailer using the transport.
$mailer = new Swift_Mailer($transport);

// Send the message.
$result = $mailer->send($message, $failures);

// Send the result.
respond(201, ['success' => $result, 'failure' => count($failures)]);
