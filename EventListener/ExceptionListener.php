<?php

namespace DMo\Colja\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();

        $error = ['message' => $exception->getMessage(), 'code' => $exception->getCode()];

        $debug = $_SERVER['APP_DEBUG'];
        if (!empty($debug)) {
            $error['file'] = $exception->getFile();
            $error['line'] = $exception->getLine();
            $error['trace'] = $exception->getTrace();
        }
        $data = ['errors' => [$error]];
        $response = new JsonResponse($data);

        // sends the modified response object to the event
        $event->setResponse($response);
    }
}
