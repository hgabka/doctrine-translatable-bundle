<?php

namespace Hgabka\Doctrine\TranslatableBundle;

use Hgabka\Doctrine\TranslatableBundle\DependencyInjection\Compiler\DriverChainCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * PrezentDoctrineTranslatableBundle
 *
 * @see Bundle
 */
class HgabkaDoctrineTranslatableBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DriverChainCompilerPass());
    }
}
