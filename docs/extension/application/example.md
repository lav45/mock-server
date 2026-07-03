# Example: Application extension

A worked example of a custom `Application` extension. For the layer contract and the list of built-in extensions see
[Application extensions](../application.md); for the directory layout, interfaces, and how to run see
[Custom Extensions](../../extension.md).

## Audit a matched mock

Read a custom `audit` block from the matched mock and log it. Because the values live in the mock file, they can use the
full template syntax — the internal core middleware resolves them before this middleware sees them.

`Extension/Audit/AuditExtension.php`:

```php
<?php declare(strict_types=1);

namespace MockServer\Extension\Audit;

use Lav45\MockServer\Extension\ExtensionProvider;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Extension\Middleware;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final readonly class AuditExtension implements ExtensionProvider
{
    public function type(): ExtensionType
    {
        return ExtensionType::Application;
    }

    public function create(ContainerInterface $container, array $config): Middleware
    {
        return new AuditMiddleware(
            logger: $container->get(LoggerInterface::class),
        );
    }
}
```

`Extension/Audit/AuditMiddleware.php`:

```php
<?php declare(strict_types=1);

namespace MockServer\Extension\Audit;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Middleware;
use Psr\Log\LoggerInterface;

final readonly class AuditMiddleware implements Middleware
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function process(ServerRequest $request, RequestHandler $next): ServerResponse
    {
        $audit = $request->getAttribute('data')['audit'] ?? null;
        if ($audit !== null) {
            $this->logger->info('mock audit', $audit);
        }
        return $next->handleRequest($request);
    }
}
```

A mock that carries an `audit` section:

```json
[
    {
        "request": {
            "method": "GET",
            "path": "/user/{id}"
        },
        "audit": {
            "actor": "{{request.params.id}}",
            "at": "{{date.format('c')}}",
            "trace": "{{faker.uuid}}"
        },
        "response": {
            "type": "content",
            "body": {
                "id": "{{request.params.id}}"
            }
        }
    }
]
```

> **Schema validation.** By default mock files are validated against `schema/mock.schema.json`, which rejects unknown
> top-level keys (`additionalProperties: false`). A custom key such as `audit` will not pass until you either disable
> validation or supply your own schema. Both are controlled by the `schema` key in the server config:
>
> ```yaml
> # Disable validation entirely — any structure is accepted:
> # schema: /app/schema/mock.schema.json
>
> # …or validate against your own schema that permits the custom key:
> schema: /app/schema/my.schema.json
> ```
>
> Commenting the key out turns validation off; pointing it at another file validates against that schema instead.

## Register and run

```yaml
extensions:
  - class: Lav45\MockServer\Extension\Content\ContentExtension
  - class: MockServer\Extension\Audit\AuditExtension
```

```shell
~$ docker run --rm -it --init \
    -v $(pwd)/mocks:/app/mocks \
    -v $(pwd)/Extension:/app/Extension \
    -v $(pwd)/config.yaml:/app/etc/config.yaml \
    -p 8080:8080 \
    lav45/mock-server:latest
```

A request to a matched route logs the resolved audit data:

```shell
~$ curl -s http://127.0.0.1:8080/user/42
{"id":"42"}
```

```
mock-server.INFO: mock audit {"actor":"42","at":"2026-07-04T09:46:03+00:00","trace":"9f2a...c1"} []
```
