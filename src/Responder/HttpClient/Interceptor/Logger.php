<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder\HttpClient\Interceptor;

use Amp\Cancellation;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Http\Client\ApplicationInterceptor;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\HttpStatus;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class Logger implements ApplicationInterceptor
{
    use ForbidCloning;
    use ForbidSerialization;

    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function request(
        Request            $request,
        Cancellation       $cancellation,
        DelegateHttpClient $httpClient,
    ): Response {
        $response = $httpClient->request($request, $cancellation);

        $isOk = HttpStatus::isSuccessful($response->getStatus());

        if ($request->hasAttribute('logLabel')) {
            $message = $request->getAttribute('logLabel') . ': ';
        } else {
            $message = '';
        }
        $message .= "{$response->getStatus()} {$request->getMethod()} {$request->getUri()}";

        if ($isOk) {
            $this->logger->info($message);
        } else {
            $this->logger->warning($message);
        }

        return $response;
    }
}
