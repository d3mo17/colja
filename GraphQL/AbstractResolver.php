<?php

namespace DMo\Colja\GraphQL;

abstract class AbstractResolver
{
    /**
     * @var ResolverManager $resolverManager
     */
    private $resolverManager;

    /**
     * User constructor.
     * @param ResolverManager $resolverManager
     */
    public function __construct(ResolverManager $resolverManager)
    {
        $this->resolverManager = $resolverManager;
    }

    /**
     * @return ResolverManager
     */
    public function getResolverManager(): ResolverManager
    {
        return $this->resolverManager;
    }
}
