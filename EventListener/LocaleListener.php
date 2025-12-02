<?php

namespace Hgabka\Doctrine\TranslatableBundle\EventListener;

use Hgabka\Doctrine\Translatable\EventListener\TranslatableListener;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Inject current locale in the TranslatableListener
 *
 * @see EventSubscriberInterface
 */
#[AsEventListener(priority: 10)]
class LocaleListener
{
    public function __construct(private readonly TranslatableListener $translatableListener)
    {
    }

    /**
     * Set request locale
     *
     * @param RequestEvent $event
     *
     * @return void
     */
    public function __invoke(RequestEvent $event)
    {
        $this->translatableListener->setCurrentLocale($event->getRequest()->getLocale());
    }
}
