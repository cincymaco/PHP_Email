<?php
declare(strict_types=1);

/**
 * @param int $status
 * @param mixed $content
 */
function respond(int $status, $content = null): void
{
    global $logger;

    if ($status === 500) {
        $logger->critical($content);
    }
    http_response_code($status);
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header('Access-Control-Allow-Headers: Accept, Cache-Control, Origin');
        header('Access-Control-Allow-Methods: OPTIONS, POST');
        header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
    }
    if ($content) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($content, JSON_PRETTY_PRINT);
        echo PHP_EOL;
    }
    exit();
}

/**
 * @param Throwable $throwable
 */
function exception_handler(Throwable $throwable): void
{
    respond(500, $throwable->getMessage());
}
