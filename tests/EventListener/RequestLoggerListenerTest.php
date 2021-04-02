<?php

namespace Mtarld\ApiPlatformMsBundle\Tests\EventListener;

use Mtarld\ApiPlatformMsBundle\Event\RequestEvent;
use Mtarld\ApiPlatformMsBundle\EventListener\RequestLoggerListener;
use Mtarld\ApiPlatformMsBundle\Microservice\Microservice;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class RequestLoggerListenerTest extends KernelTestCase
{
    /**
     * @dataProvider microserviceProvider
     */
    public function testRequestIsLoggedViaAnEvent(Microservice $microservice, string $microserviceName, string $method, string $uri, array $options)
    {
        $event = new RequestEvent($microservice, $method, $uri, $options);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with('[GenericHttpClient] for Microservice "{microservice_name}" calling {method} {url}', [
                'method' => $method,
                'microservice_name' => $microserviceName,
                'url' => $uri,
                'options' => $options,
            ])
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RequestLoggerListener($logger));
        $dispatcher->dispatch($event);
    }

    public function microserviceProvider(): iterable
    {
        yield [new Microservice($msName = 'product', 'https://localhost', '/api', 'jsonld'),  $msName, 'GET', '/products', []];
        yield [new Microservice($msName = 'user', 'https://domain.com', '/app', 'json'), $msName, 'DELETE', '/users', ['Authorization' => 'Bearer 4242']];
    }
}
