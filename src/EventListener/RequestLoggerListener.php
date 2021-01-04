<?php

namespace Mtarld\ApiPlatformMsBundle\EventListener;

use Mtarld\ApiPlatformMsBundle\Event\RequestEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RequestLoggerListener implements EventSubscriberInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onHttpRequest(RequestEvent $event): void
    {
        $this->logger->debug('[GenericHttpClient] for Microservice "{microservice_name}" calling {method} {url}', [
            'method' => $event->getMethod(),
            'microservice_name' => $event->getMicroservice()->getName(),
            'url' => $event->getUri(),
            'options' => $event->getOptions(),
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onHttpRequest',
        ];
    }
}
