<?php

namespace Jihel\VikingPayBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class JihelVikingPayExtension
 *
 * @author Joseph LEMOINE <j.lemoine@ludi.cat>
 */
class JihelVikingPayExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->registerParameters($configs, $container);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('adapter.yaml');
        $loader->load('form.yaml');
        $loader->load('plugin.yaml');
    }

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     * @return $this
     */
    protected function registerParameters(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('jihel.viking_pay.accounts', $config['accounts']);

        return $this;
    }
}
