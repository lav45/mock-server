<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Controller;

use Faker\Generator as Faker;
use Lav45\MockServer\Application\Data\Mock\v1\Mock;
use Lav45\MockServer\Infrastructure\Wrapper\HttpClient;
use Psr\Log\LoggerInterface;

final readonly class RequestFactory
{
    public function __construct(
        private Faker           $faker,
        private HttpClient      $httpClient,
        private LoggerInterface $logger,
    ) {}

    public function create(Mock $mock): Request
    {
        return new Request(
            faker: $this->faker,
            logger: $this->logger,
            httpClient: $this->httpClient,
            mockData: $mock,
        );
    }
}
