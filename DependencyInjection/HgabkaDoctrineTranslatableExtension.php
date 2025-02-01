<?php

namespace Hgabka\Doctrine\TranslatableBundle\DependencyInjection;

use Hgabka\Doctrine\Translatable\EventListener\TranslatableListener;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class HgabkaDoctrineTranslatableExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->getDefinition(TranslatableListener::class)
                  ->addMethodCall('setCurrentLocale', [$config['fallback_locale']])
                  ->addMethodCall('setFallbackLocale', [$config['fallback_locale']]);

        $this->loadSonata($container, $loader);
    }

    /**
     * Load the Sonata configuration, if the versions is supported
     *
     * @param ContainerBuilder     $container
     * @param Loader\XmlFileLoader $loader
     */
    private function loadSonata(ContainerBuilder $container, Loader\XmlFileLoader $loader): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['SonataDoctrineORMAdminBundle'])) {
            return; // Sonata not installed
        }

        $refl = new \ReflectionMethod('Sonata\\DoctrineORMAdminBundle\\Filter\\StringFilter', 'filter');

        if (method_exists($refl, 'getReturnType')) {
            if ($returnType = $refl->getReturnType()) {
                return; // Not compatible with this Sonata version
            }
        }

        $loader->load('sonata.xml');
    }
}
