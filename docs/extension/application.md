# Application extensions

`Application` middleware runs **innermost**, only after a route matched. If no mock matched the request there is nothing
to act on, so it is skipped (the `System` layer still runs). This is where you consume **user data from the matched mock
file** — the built-in responders are all `Application` extensions.

By the time an `Application` middleware runs, the internal core middleware has already:

1. matched the route and attached the mock definition, and
2. resolved every template in it — `env`, `{{request.params.*}}`, `{{faker.*}}`, `{{date.*}}`.

So you read **final, resolved values** straight from the request attributes:

| Attribute | Contents                                                                                            |
|-----------|-----------------------------------------------------------------------------------------------------|
| `data`    | The matched mock entry (its `request`, `response`, and any custom keys), templates already resolved |
| `params`  | Route parameters extracted from the path (`/user/{id}` → `['id' => '42']`)                          |
| `parser`  | The parser used for on-the-fly resolution of additional values                                      |

## Built-in extensions

| Extension                               | Description                                                                                                                                                    |
|-----------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [Response](application/response.md)     | The response returned to the client — [content](application/response/content.md), [proxy](application/response/proxy.md), [data](application/response/data.md) |
| [Delay](application/delay.md)           | Delay any response by `response.delay` seconds, regardless of its type                                                                                         |
| [Conditions](application/conditions.md) | Conditional response overrides evaluated before the default response                                                                                           |
| [Direct](application/direct.md)         | Delegate response generation to a remote server                                                                                                                |
| [WebHooks](application/webhooks.md)     | Fire outgoing HTTP requests after the response is sent                                                                                                         |

## Write your own

See [Example: Application extension](application/example.md) for a complete custom extension, and
[Custom Extensions](../extension.md) for the directory layout, interfaces, and how to run.
