<?php

namespace DMo\Colja\GraphQL;

use DMo\Colja\Controller\GraphQLController;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ResolverManager implements ResolverManagerInterface, LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var GraphQLController
     */
    private $controller;

    /**
     * Creates a specific resolver
     * @param string $classname The name of a class that is an AbstractResolver
     * @param string $methodName
     * @return \Closure
     */
    protected function createResolver(string $classname, $methodName): \Closure
    {
        return function (array $root = null, array $args, $context, ResolveInfo $info) use ($classname, $methodName) {
            if (!is_a($classname, AbstractResolver::class, true)) {
                throw new \InvalidArgumentException(
                    $classname . ' has to be of type '. AbstractResolver::class . '!'
                );
            }
            $resolver = new $classname($this);
            return $resolver->$methodName($root, $args, $context, $info);
        };
    }

    /**
     * Helper method to create resolvers from configuration
     * @param array $resolver
     * @param array $config
     * @return void
     */
    private function placeResolversByConfig(array &$resolver, array $config)
    {
        foreach(['query', 'mutation'] as $kind) {
            if (empty($config[$kind])) {
                continue;
            }

            $resolver[$kind] = $resolver[$kind] ?? [];
            foreach ($config[$kind] as $fieldname => $methodConf) {
                $resolver[ucfirst($kind)][$fieldname] = $this->createResolver(
                    $methodConf['class'],
                    $methodConf['method']
                );
            }
        }
    }

    /**
     * Returns all resolvers
     * @return array
     */
    public function getResolvers(): array
    {
        $cparams = $this->controller->getConfigParameter('d_mo_colja');

        $resolver = [];
        $this->placeResolversByConfig($resolver, $cparams);

        foreach ($cparams['extensions'] as $extConfig) {
            $this->placeResolversByConfig($resolver, $extConfig);
        }

        return $resolver;
    }

    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @throws \LogicException If DoctrineBundle is not available
     * @return \Doctrine\Common\Persistence\ManagerRegistry
     *
     * @final
     */
    public function getDoctrine()
    {
        if (!$this->container->has('doctrine')) {
            throw new \LogicException(
                'The DoctrineBundle is not registered in your application. '
                . 'Try running "composer require symfony/orm-pack".'
            );
        }

        return $this->container->get('doctrine');
    }

    /**
     * 
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @return GraphQLController
     */
    public function getController(): GraphQLController
    {
        return $this->controller;
    }

    /**
     * @param GraphQLController $controller
     * @return $this
     */
    public function setController(GraphQLController $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
