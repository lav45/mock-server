# Direct

Delegate response generation to a remote server. When a request matches a mock definition that contains a `direct`
property, the mock server forwards the request to the specified URL and uses the response to build the final reply to
the client.

## `direct.url`

URL of the remote server that will provide the dynamic response configuration.

| Types  | Default |
|--------|---------|
| string | `null`  |

```json
[
    {
        "request": {
            "url": "/forward/{id}"
        },
        "direct": {
            "url": "http://internal.api/dynamic-response"
        }
    }
]
```

## `direct.headers`

Additional headers to send to the direct server. These headers are merged with the original request headers (original
headers take precedence unless overridden).

| Types  | Default |
|--------|---------|
| object | `{}`    |

```json
[
    {
        "request": {
            "url": "/forward/{id}"
        },
        "direct": {
            "url": "http://internal.api/dynamic-response",
            "headers": {
                "X-Source": "mock-server",
                "Authorization": "Bearer token123"
            }
        }
    }
]
```

## How it works

- The mock server receives a request that matches a mock definition containing a `direct` property.
- It forwards the request to `direct.url` using the same HTTP method, headers (merged with `direct.headers`), query
  parameters, and body as the original request.
- The direct server must respond with a JSON object that conforms to the [response](response.md) schema. This object can
  include:
    - `response` – defines the final HTTP response (status, headers, body, etc.)
    - `webhooks` – an array of [webhooks](webhooks.md) to trigger after sending the response
- The mock server processes the returned configuration (evaluating any placeholders or Faker expressions) and sends the
  final response back to the original client.

## Direct server response example

The direct server returns a JSON that describes the final response and optionally webhooks:

```json
{
    "response": {
        "type": "content",
        "headers": {
            "X-Custom": "dynamic-value"
        },
        "json": {
            "message": "Processed {{request.get.id}}"
        }
    },
    "webhooks": [
        {
            "url": "https://webhook.site/audit",
            "json": {
                "event": "forwarded",
                "timestamp": "{{faker.unixTime}}"
            }
        }
    ]
}
```

## Placeholders and Faker

The direct server can return strings containing placeholders or [Faker](https://fakerphp.github.io) expressions. These
will be resolved by the mock
server using the original request context. Available placeholders:

| Placeholder             | Description                                |
|-------------------------|--------------------------------------------|
| `{{request.urlParams}}` | URL parameters from the original request   |
| `{{request.get}}`       | Query parameters from the original request |
| `{{request.post}}`      | Parsed body from the original request      |
| `{{request.headers}}`   | Headers from the original request          |

For a full list of placeholders and Faker usage, see Faker integration.

## Escaping curly braces

When the direct server returns a value that contains literal curly braces `{` or `}` (and you want to prevent them from
being interpreted as placeholders), you can escape them by writing `\{` and `\}` respectively. The mock server will
replace `\{` with `{` and `\}` with `}` after all placeholder substitutions are done on the direct server’s response.

For example, if your direct server returns:

```json
{
    "response": {
        "json": {
            "id": "\\{\\{request.urlParams.id\\}\\}"
        }
    }
}
```

The final response sent to the client will contain:

```json
{
    "regex": "{{request.urlParams.id}}"
}
```

---
**Note**: 
In practice this is rarely needed, but it is available for edge cases where you need literal braces in the output.
---

## Complete example

```json
[
    {
        "env": {
            "directEndpoint": "http://127.0.0.1:8080/direct/data"
        },
        "request": {
            "method": "PUT",
            "url": "/direct/{id:\\d+}"
        },
        "direct": {
            "url": "{env.directEndpoint}",
            "headers": {
                "x-status": "active"
            }
        }
    },
    {
        "request": {
            "method": "PUT",
            "url": "/direct/data"
        },
        "response": {
            "type": "content",
            "json": {
                "response": {
                    "type": "content",
                    "headers": {
                        "x-status": "open"
                    },
                    "json": {
                        "originalId": "{{request.urlParams.id}}",
                        "originalQuery": "{{request.get}}",
                        "originalBody": "{{request.post}}"
                    }
                },
                "webhooks": [
                    {
                        "url": "https://api.site.com/webhook",
                        "json": {
                            "id": "{{faker.uuid}}"
                        }
                    }
                ]
            }
        }
    }
]
```

---
**Note**: 
The direct property is intended for scenarios where the response must be generated dynamically by an external service. 
The direct server is responsible for returning a valid response configuration.
---
