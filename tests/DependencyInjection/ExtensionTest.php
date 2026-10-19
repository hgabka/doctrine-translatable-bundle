<?php

namespace Hgabka\Doctrine\TranslatableBundle\Tests\DependencyInjection;

use Hgabka\Doctrine\Translatable\EventListener\TranslatableListener;
use Hgabka\Doctrine\TranslatableBundle\DependencyInjection\HgabkaDoctrineTranslatableExtension;
use Hgabka\Doctrine\TranslatableBundle\EventListener\LocaleListener;
use Metadata\Driver\DriverChain;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtensionTest extends TestCase
{
    private function container(array $config = [], array $bundles = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', $bundles);
        (new HgabkaDoctrineTranslatableExtension())->load([$config], $container);

        return $container;
    }

    public function testServicesAreRegistered(): void
    {
        $container = $this->container();

        $this->assertTrue($container->hasDefinition(DriverChain::class));
        $this->assertTrue($container->hasDefinition(MetadataFactory::class));
        $this->assertTrue($container->hasDefinition(TranslatableListener::class));
        $this->assertTrue($container->hasDefinition(LocaleListener::class));
    }

    public function testListenerReceivesFallbackLocale(): void
    {
        $calls = $this->container(['fallback_locale' => 'hu'])->getDefinition(TranslatableListener::class)->getMethodCalls();

        $this->assertContains(['setFallbackLocale', ['hu']], $calls);
        $this->assertContains(['setCurrentLocale', ['hu']], $calls);
    }

    public function testSonataFilterIsSkippedWithoutSonata(): void
    {
        $container = $this->container();

        $this->assertFalse($container->hasDefinition('hgabka_doctrine_translatable.sonata.filter'));
    }
}
