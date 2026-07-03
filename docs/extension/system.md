# System extensions

`System` middleware runs **outermost** in the pipeline. It sees the raw request before routing and wraps **every**
response the server produces — including `404` (no route) and `405` (method not allowed). This makes it the place for
cross-cutting concerns that must apply regardless of whether any mock matched: authentication, rate limiting, custom
headers, request logging, tracing.

Because it wraps the whole pipeline, a `System` middleware can also short-circuit the request — return your own
`ServerResponse` from `process()` without calling `$next` to reject the request outright (e.g. a missing API key).

## Built-in extensions

| Extension              | Description                                               |
|------------------------|-----------------------------------------------------------|
| [CORS](system/cors.md) | CORS headers and preflight handling (disabled by default) |

## Write your own

See [Example: System extension](system/example.md) for a complete custom extension, and
[Custom Extensions](../extension.md) for the directory layout, interfaces, and how to run.
