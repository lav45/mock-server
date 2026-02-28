<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\HttpClient\Interceptor;

use Amp\Cancellation;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Http\Client\ApplicationInterceptor;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\Client\SocketException;

final class RetryRequests implements ApplicationInterceptor
{
    use ForbidCloning;
    use ForbidSerialization;

    public function __construct(private readonly int $retryLimit) {}

    public function request(
        Request            $request,
        Cancellation       $cancellation,
        DelegateHttpClient $httpClient,
    ): Response {
        $attempt = 1;

        do {
            $clonedRequest = clone $request;
            try {
                return $httpClient->request($request, $cancellation);
            } catch (SocketException $exception) {
                if ($request->isIdempotent() || $request->isUnprocessed()) {
                    // Request was deemed retryable by connection, so carry on.
                    $request = $clonedRequest;
                }
            }
        } while ($attempt++ <= $this->retryLimit);

        throw $exception;
    }
}
