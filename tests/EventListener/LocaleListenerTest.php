<?php

namespace Hgabka\Doctrine\TranslatableBundle\Tests\EventListener;

use Hgabka\Doctrine\Translatable\EventListener\TranslatableListener;
use Hgabka\Doctrine\TranslatableBundle\EventListener\LocaleListener;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LocaleListenerTest extends TestCase
{
    public function testRequestLocaleIsPassedToTranslatableListener(): void
    {
        $translatableListener = new TranslatableListener($this->createStub(MetadataFactory::class));
        $request = Request::create('/');
        $request->setLocale('hu');

        (new LocaleListener($translatableListener))(
            new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST)
        );

        $this->assertSame('hu', $translatableListener->getCurrentLocale());
    }
}
