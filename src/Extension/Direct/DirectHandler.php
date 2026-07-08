<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Direct;

use Lav45\MockServer\Domain\Direct;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Helper\ArrayHelper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class DirectHandler
{
    public function __construct(
        private HttpClient      $httpClient,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function request(Direct $direct): DirectDataInjector
    {
        $response = $this->httpClient->request(
            uri: $direct->url->value,
            method: $direct->method->value,
            headers: $direct->headers->toArray(),
            body: $direct->body->stream->read(),
        );
        $body = $response->getBody()->stream->read();
        if (\json_validate($body) && $this->isSuccessful($response->getStatus())) {
            $directData = \json_decode($body, true, flags: JSON_THROW_ON_ERROR);
            $directData = ArrayHelper::map($directData, static function (string $value): string {
                return \str_replace(['\\{', '\\}'], ['{', '}'], $value);
            });
            return new DirectDataInjector($directData, $this->logger);
        }
        throw new \RuntimeException(message: $body, code: $response->getStatus());
    }

    private function isSuccessful(int $status): bool
    {
        return $status >= 200 && $status < 300;
    }
}
