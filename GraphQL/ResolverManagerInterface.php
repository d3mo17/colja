<?php

namespace DMo\Colja\GraphQL;

use DMo\Colja\Controller\GraphQLController;
use Psr\Container\ContainerInterface;

interface ResolverManagerInterface
{
    function setContainer(ContainerInterface $container);
    function setController(GraphQLController $controller);
    function getController(): GraphQLController;
    function getResolvers();
}
