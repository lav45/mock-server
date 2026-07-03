# Example: System extension

A worked example of a custom `System` extension. For the layer contract and the list of built-in extensions see
[System extensions](../system.md); for the directory layout, interfaces, and how to run see
[Custom Extensions](../../extension.md).

## A request-id header

Add an `X-Request-Id` header to every response. The header name is configurable through the `config` block.

`Extension/RequestId/RequestIdExtension.php`:

```php
<?php declare(strict_types=1);

namespace MockServer\Extension\RequestId;

use Lav45\MockServer\Extension\ExtensionProvider;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Extension\Middleware;
use Psr\Container\ContainerInterface;

final readonly class RequestIdExtension implements ExtensionProvider
{
    public function type(): ExtensionType
    {
        return ExtensionType::System;
    }

    public function create(ContainerInterface $container, array $config): Middleware
    {
        return new RequestIdMiddleware(
            header: $config['header'] ?? 'X-Request-Id',
        );
    }
}
```

`Extension/RequestId/RequestIdMiddleware.php`:

```php
<?php declare(strict_types=1);

namespace MockServer\Extension\RequestId;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Middleware;

final readonly class RequestIdMiddleware implements Middleware
{
    public function __construct(
        private string $header,
    ) {}

    public function process(ServerRequest $request, RequestHandler $next): ServerResponse
    {
        $response = $next->handleRequest($request);
        $response->setHeader($this->header, \bin2hex(\random_bytes(8)));
        return $response;
    }
}
```

## Register and run

```yaml
extensions:
  - class: Lav45\MockServer\Extension\Content\ContentExtension
  - class: MockServer\Extension\RequestId\RequestIdExtension
    config:
      header: X-Trace-Id
```

```shell
~$ docker run --rm -it --init \
    -v $(pwd)/mocks:/app/mocks \
    -v $(pwd)/Extension:/app/Extension \
    -v $(pwd)/config.yaml:/app/etc/config.yaml \
    -p 8080:8080 \
    lav45/mock-server:latest
```

The header appears on every response, including a route that does not exist:

```shell
~$ curl -si http://127.0.0.1:8080/nope | grep -i x-trace-id
x-trace-id: 3f8a1c9e7b2d0a64
```
