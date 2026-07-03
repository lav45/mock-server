# Custom Extensions

Extensions are HTTP-level middleware. They let you add your own behaviour to the request/response pipeline — from
headers and authentication that apply to every response, down to logic that reads custom sections of a matched mock
file. You can ship an extension without rebuilding the image: drop the classes into the `Extension/` directory, register
them in the config, and mount both into the container.

[CORS](extension/system/cors.md) is a built-in example.

## The `Extension/` directory

The image reserves a dedicated namespace for user code, autoloaded via PSR-4:

| Namespace               | Directory        |
|-------------------------|------------------|
| `MockServer\Extension\` | `/app/Extension` |

A class `MockServer\Extension\RequestId\RequestIdExtension` is expected at
`Extension/RequestId/RequestIdExtension.php`. Mount your local `Extension/` folder over `/app/Extension` and the server
picks the classes up on start — no `composer install` required.

An extension is two small pieces: a **provider** (`ExtensionProvider`) that the server instantiates once at startup, and
the **middleware** (`Middleware`) it builds. See [Interfaces](#interfaces) for the contracts and
[Container services](#container-services) for what `create()` can pull from the container.

## Running

Register the extension in the server config (`/app/etc/config.yaml` by default, or the file pointed to by
`CONFIG_PATH`). Each entry is a fully-qualified class name plus an optional `config` block, passed to `create()`:

```yaml
extensions:
  - class: Lav45\MockServer\Extension\Content\ContentExtension
  - class: Lav45\MockServer\Extension\Proxy\ProxyExtension
  - class: MockServer\Extension\RequestId\RequestIdExtension
    config:
      header: X-Trace-Id
```

> **Note:** the `extensions` list is the complete set — a custom config **replaces** the default one. Keep the built-in
> extensions you still need (e.g. `content`, `proxy`) alongside your own.

The `class` must implement `ExtensionProvider`; anything else fails fast at startup with `Invalid extension: <class>`.

Mount the `Extension/` directory and your config, then start the server as usual:

```shell
~$ docker run --rm -it --init \
    -v $(pwd)/mocks:/app/mocks \
    -v $(pwd)/Extension:/app/Extension \
    -v $(pwd)/config.yaml:/app/etc/config.yaml \
    -p 8080:8080 \
    lav45/mock-server:latest
```

## Execution order

Middleware runs grouped by `ExtensionType`, outermost first. There are two types:

| Type          | Runs      | Purpose                                                      |
|---------------|-----------|--------------------------------------------------------------|
| `System`      | outermost | Wraps every response, including routing errors (`404`/`405`) |
| `Application` | innermost | Runs after a route matched, reads the matched mock data      |

Between them sits the **internal core middleware** — the built-in routing and template resolution — a fixed layer you
cannot configure or reorder. So the effective pipeline is `System → routing + template resolution → Application`.

Extensions are first grouped by `ExtensionType` — the list order never moves one across types (an `Application` entry
can never run before a `System` one, however you sort the list). **Within a single type** the order follows the
`extensions` list top to bottom, so a custom extension is interleaved among the built-ins of the same type exactly where
you place its line.

To **disable** an extension, remove or comment out its line in `extensions` — there is no separate on/off flag. Because
a custom config **replaces** the default list, dropping a line is all it takes.

> Your own extensions choose between the two types:
>
> - **[System](extension/system.md)** — cross-cutting concerns that must touch every response.
> - **[Application](extension/application.md)** — logic that consumes user data from the matched mock file.

## Interfaces

### `ExtensionProvider`

```php
namespace Lav45\MockServer\Extension;

use Psr\Container\ContainerInterface;

interface MiddlewareFactory
{
    public function create(ContainerInterface $container, array $config): Middleware;
}

interface ExtensionProvider extends MiddlewareFactory
{
    public function type(): ExtensionType;
}
```

- `create()` — builds the middleware. `$config` is the `config` block from the YAML entry; `$container` gives access to
  the server services listed [below](#container-services).
- `type()` — returns the `ExtensionType` that places the middleware in the pipeline (
  see [Execution order](#execution-order)).

### `Middleware`

```php
namespace Lav45\MockServer\Extension;

interface Middleware
{
    public function process(ServerRequest $request, RequestHandler $next): ServerResponse;
}
```

Call `$next->handleRequest($request)` to delegate to the rest of the pipeline, then inspect or modify the
`ServerResponse` before returning it. Return your own `ServerResponse` to short-circuit and skip everything downstream.

## Container services

`create()` receives a PSR-11 container exposing these services:

| Service                                    | Use                                           |
|--------------------------------------------|-----------------------------------------------|
| `Lav45\MockServer\DataFactory\DataBuilder` | Build domain objects from mock data           |
| `Lav45\MockServer\Engine\HttpClient`       | Outgoing HTTP requests (`->withLabel()`)      |
| `Lav45\MockServer\Engine\WebHookQueue`     | Enqueue webhooks                              |
| `Lav45\MockServer\Parser\VariableParser`   | Template parsing (`{{faker.*}}`, `{{env.*}}`) |
| `FastRoute\Dispatcher`                     | The route dispatcher                          |
| `Psr\Log\LoggerInterface`                  | Logging                                       |
