<?php

namespace Hgabka\Doctrine\TranslatableBundle\EventListener;

use Hgabka\Doctrine\Translatable\EventListener\TranslatableListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Inject current locale in the TranslatableListener
 *
 * @see EventSubscriberInterface
 */
class LocaleListener implements EventSubscriberInterface
{
    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    /**
     * Constructor
     *
     * @param TranslatableListener $translatableListener
     */
    public function __construct(TranslatableListener $translatableListener)
    {
        $this->translatableListener = $translatableListener;
    }

    /**
     * Set request locale
     *
     * @param RequestEvent $event
     * @return void
     */
    public function onKernelRequest(KernelEvent $event)
    {
        $this->translatableListener->setCurrentLocale($event->getRequest()->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest', 10)),
        );
    }
}
