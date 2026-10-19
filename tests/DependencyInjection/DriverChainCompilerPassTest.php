<?php

namespace Hgabka\Doctrine\TranslatableBundle\Tests\DependencyInjection;

use Hgabka\Doctrine\Translatable\Mapping\Driver\DoctrineAdapter;
use Hgabka\Doctrine\TranslatableBundle\DependencyInjection\Compiler\DriverChainCompilerPass;
use Metadata\Driver\DriverChain;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DriverChainCompilerPassTest extends TestCase
{
    public function testAdapterIsAddedForEveryEntityManager(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('doctrine.entity_managers', [
            'default' => 'doctrine.orm.default_entity_manager',
            'other' => 'doctrine.orm.other_entity_manager',
        ]);
        $container->setDefinition(DriverChain::class, new Definition(DriverChain::class));

        (new DriverChainCompilerPass())->process($container);

        $calls = $container->getDefinition(DriverChain::class)->getMethodCalls();
        $this->assertCount(2, $calls);

        foreach ($calls as $i => [$method, $arguments]) {
            $this->assertSame('addDriver', $method);
            $adapter = $arguments[0];
            $this->assertSame([DoctrineAdapter::class, 'fromMetadataDriver'], $adapter->getFactory());
            $this->assertEquals(
                [new Reference(sprintf('doctrine.orm.%s_metadata_driver', 0 === $i ? 'default' : 'other'))],
                $adapter->getArguments()
            );
        }
    }

    public function testNothingIsAddedWithoutEntityManagers(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('doctrine.entity_managers', []);
        $container->setDefinition(DriverChain::class, new Definition(DriverChain::class));

        (new DriverChainCompilerPass())->process($container);

        $this->assertSame([], $container->getDefinition(DriverChain::class)->getMethodCalls());
    }
}
