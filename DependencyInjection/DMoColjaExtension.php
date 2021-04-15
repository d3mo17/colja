<?php

namespace DMo\Colja\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class DMoColjaExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('d_mo_colja', $config);
        array_key_exists('client', $config)
            && $container->setParameter('d_mo_colja.client', $config['client']);
        $container->setParameter(
            'd_mo_colja.option_request_user_restricted',
            $config['option_request_user_restricted']
        );

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
