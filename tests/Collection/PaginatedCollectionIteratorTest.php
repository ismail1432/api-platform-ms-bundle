<?php

namespace Mtarld\ApiPlatformMsBundle\Tests\Collection;

use Mtarld\ApiPlatformMsBundle\Collection\Collection;
use Mtarld\ApiPlatformMsBundle\Collection\PaginatedCollectionIterator;
use Mtarld\ApiPlatformMsBundle\Collection\Pagination;
use Mtarld\ApiPlatformMsBundle\Exception\CollectionNotIterableException;
use Mtarld\ApiPlatformMsBundle\Microservice\MicroservicePool;
use Mtarld\ApiPlatformMsBundle\Tests\Fixtures\App\src\Dto\PuppyResourceDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @group collection
 * @group http
 */
class PaginatedCollectionIteratorTest extends KernelTestCase
{
    public function setUp(): void
    {
        static::bootKernel();
    }

    public function testIterateOverPaginatedCollection(): void
    {
        $this->mockHttpClient();

        /** @var MicroservicePool $microservices */
        $microservices = static::$container->get('api_platform_ms.microservice_pool');

        $collection = new Collection([
            new PuppyResourceDto('/api/puppies/1', 1, 'foo'),
            new PuppyResourceDto('/api/puppies/2', 1, 'bar'),
        ], 5, new Pagination('/api/puppies?page=1', '/api/puppies?page=1', '/api/puppies?page=3', null, '/api/puppies?page=2'));
        $collection = $collection->withMicroservice($microservices->get('bar'));

        $expectedElements = [
            new PuppyResourceDto('/api/puppies/1', 1, 'foo'),
            new PuppyResourceDto('/api/puppies/2', 1, 'bar'),
            new PuppyResourceDto('/api/puppies/3', 3, 'baz'),
            new PuppyResourceDto('/api/puppies/4', 4, 'oof'),
            new PuppyResourceDto('/api/puppies/5', 5, 'rab'),
        ];

        /** @var PaginatedCollectionIterator $iterator */
        $iterator = static::$container->get(PaginatedCollectionIterator::class);

        $index = 0;
        foreach ($iterator->iterateOver($collection) as $element) {
            self::assertEquals($expectedElements[$index++], $element);
        }

        self::assertEquals(5, $index);
    }

    public function testIterateOverPaginatedCollectionWithoutMicroserviceMetadata(): void
    {
        $collection = new Collection([
            new PuppyResourceDto('/api/puppies/1', 1, 'foo'),
            new PuppyResourceDto('/api/puppies/2', 1, 'bar'),
        ], 5, new Pagination('/api/puppies?page=1', '/api/puppies?page=1', '/api/puppies?page=3', null, '/api/puppies?page=2'));

        /** @var PaginatedCollectionIterator $iterator */
        $iterator = static::$container->get(PaginatedCollectionIterator::class);

        $this->expectException(CollectionNotIterableException::class);
        iterator_to_array($iterator->iterateOver($collection));
    }

    private function mockHttpClient(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects(self::exactly(2))
            ->method('request')
            ->withConsecutive([
                'GET',
                '/api/puppies?page=2',
                [
                    'base_uri' => 'https://localhost',
                    'headers' => [
                        'Content-Type' => 'application/ld+json',
                        'Accept' => 'application/ld+json',
                    ],
                ],
            ], [
                'GET',
                '/api/puppies?page=3',
                [
                    'base_uri' => 'https://localhost',
                    'headers' => [
                        'Content-Type' => 'application/ld+json',
                        'Accept' => 'application/ld+json',
                    ],
                ],
            ])
            ->willReturnOnConsecutiveCalls(...$this->getPartialCollectionResponses())
        ;

        static::$container->set('test.http_client', $httpClient);
    }

    private function getPartialCollectionResponses(): array
    {
        $page2Response = $this->createMock(ResponseInterface::class);
        $page2Response
            ->method('getContent')
            ->willReturn(<<<JSON
{
    "@context":"\/contexts\/Puppy", "@id":"\/api/puppies", "@type":"hydra:Collection", "hydra:totalItems":5,
    "hydra:member":[{"@id":"\/api/puppies\/3","@type":"Puppy","id":3,"super_name":"baz"}, {"@id":"\/api/puppies\/4","@type":"Puppy","id":4,"super_name":"oof"}],
    "hydra:view": {
        "@id": "\/api/puppies?page=2",
        "hydra:first": "\/api/puppies?page=1",
        "hydra:last": "\/api/puppies?page=3",
        "hydra:previous": "\/api/puppies?page=1",
        "hydra:next": "\/api/puppies?page=3"
    }
}
JSON
            )
        ;

        $page3Response = $this->createMock(ResponseInterface::class);
        $page3Response
            ->method('getContent')
            ->willReturn(<<<JSON
{
    "@context":"\/contexts\/Puppy", "@id":"\/api/puppies", "@type":"hydra:Collection", "hydra:totalItems":5,
    "hydra:member":[{"@id":"\/api/puppies\/5","@type":"Puppy","id":5,"super_name":"rab"}],
    "hydra:view": {
        "@id": "\/api/puppies?page=3",
        "hydra:first": "\/api/puppies?page=1",
        "hydra:last": "\/api/puppies?page=3",
        "hydra:previous": "\/api/puppies?page=2"
    }
}
JSON
            )
        ;

        return [$page2Response, $page3Response];
    }
}