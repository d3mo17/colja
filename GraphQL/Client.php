<?php

namespace DMo\Colja\GraphQL;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Client
 * @package DMo\Colja\GraphQL
 */
class Client
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Client constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $endpoint
     * @param string $query
     * @param array $variables
     * @param array $headers
     * @return array|null
     * @throws \ErrorException
     */
    public function query(string $endpoint, string $query, array $variables = [], ?array $headers = []): ?array
    {
        $clientConfig = $this->container->getParameter('d_mo_colja.client');
        $response = (new \GuzzleHttp\Client())->post(
            $endpoint, [
                'headers' => array_merge(
                    ['Content-Type' => 'application/json', 'User-Agent' => $clientConfig['user_agent']],
                    $headers
                ),
                'http_errors' => false,
                'connect_timeout' => $clientConfig['connect_timeout'],
                'read_timeout' => $clientConfig['read_timeout'],
                'timeout' => $clientConfig['timeout'],
                'body' => json_encode(['query' => $query, 'variables' => $variables]),
            ]
        );
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            $errors['errors']['message'][0] = $response->getReasonPhrase();
            $errors['errors']['code'][0] = $response->getStatusCode();
        }
        $json = json_decode($response->getBody(), true);
        unset($response);
        return $json;
    }
}
