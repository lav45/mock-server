<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\HttpClient\Interceptor;

use Amp\Cancellation;
use Amp\Http\Client\ApplicationInterceptor;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\HttpStatus;
use Psr\Log\LoggerInterface;

final readonly class Logger implements ApplicationInterceptor
{
    public function __construct(
        private LoggerInterface $logger,
        private mixed           $logLevelOk,
        private mixed           $logLevelError,
    ) {}

    public function request(Request $request, Cancellation $cancellation, DelegateHttpClient $httpClient): Response
    {
        $response = $httpClient->request($request, $cancellation);

        $loggerLevel = HttpStatus::isSuccessful($response->getStatus())
            ? $this->logLevelOk
            : $this->logLevelError;

        $message = '';
        if ($request->hasAttribute('logLabel')) {
            $message .= $request->getAttribute('logLabel') . ': ';
        }
        $message .= "{$response->getStatus()} {$request->getMethod()} {$request->getUri()}";

        $this->logger->log($loggerLevel, $message);

        return $response;
    }
}
