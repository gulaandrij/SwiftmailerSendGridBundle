<?php

namespace ExpertCoder\Swiftmailer\SendGridBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class ExpertCoderSwiftmailerSendGridExtension extends Extension
{
    /**
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('expertcoder_swiftmailer_sendgrid.api_key', $config['api_key']);
        $container->setParameter('expertcoder_swiftmailer_sendgrid.categories', $config['categories']);
        $container->setParameter('expertcoder_swiftmailer_sendgrid.sandbox_mode', $config['sandbox_mode']);
    }
}
