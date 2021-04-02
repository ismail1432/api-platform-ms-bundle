<?php

namespace Mtarld\ApiPlatformMsBundle\EventListener;

use Mtarld\ApiPlatformMsBundle\Event\RequestEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Smaïne Milianni <smaine.milianni@gmail.com>
 */
class RequestLoggerListener implements EventSubscriberInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onHttpRequest(RequestEvent $event): void
    {
        $this->logger->debug('Microservice "{microservice_name}" calling {method} {url}', [
            'method' => $event->getMethod(),
            'microservice_name' => $event->getMicroservice()->getName(),
            'url' => $event->getUri(),
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onHttpRequest',
        ];
    }
}
