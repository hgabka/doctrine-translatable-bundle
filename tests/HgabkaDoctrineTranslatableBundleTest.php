<?php

namespace Hgabka\Doctrine\TranslatableBundle\Tests;

use Hgabka\Doctrine\TranslatableBundle\DependencyInjection\Compiler\DriverChainCompilerPass;
use Hgabka\Doctrine\TranslatableBundle\HgabkaDoctrineTranslatableBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HgabkaDoctrineTranslatableBundleTest extends TestCase
{
    public function testBuildRegistersDriverChainPass(): void
    {
        $container = new ContainerBuilder();

        (new HgabkaDoctrineTranslatableBundle())->build($container);

        $classes = array_map('get_class', $container->getCompilerPassConfig()->getBeforeOptimizationPasses());
        $this->assertContains(DriverChainCompilerPass::class, $classes);
    }
}
