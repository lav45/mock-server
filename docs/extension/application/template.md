# Template

A built-in [`Application`](../application.md) extension (`Lav45\MockServer\Extension\Template\TemplateExtension`) that
expands **reusable response/webhook templates** referenced from a mock. Templates live in the server config; a mock
pulls one in with a `$template.<name>` key, passes it parameters, and optionally overrides parts of it.

It ships **disabled by default**: the block is commented out in `etc/config.yaml`. Uncomment it to enable templates.

## Enable

Templates are declared under the extension's `config.template` block — each key is a template name, each value is an
arbitrary fragment (a response, a webhook, or any object):

```yaml
extensions:
  - class: Lav45\MockServer\Extension\Template\TemplateExtension
    config:
      template:
        content_response:
          type: "content"
          headers:
            content-type: "application/json"
          body: "{{template.data}}"
```

The `extensions` list is the complete set — a custom config **replaces** the default one, so keep the other built-in
extensions you still need alongside `TemplateExtension`. Place it **before** the extensions that consume its output
(`WebHookExtension`, `ContentExtension`, `ProxyExtension`, …) so expansion happens first.

## Referencing a template

Anywhere in a mock, replace an object with a single `$template.<name>` key. Its value is the **parameters** passed to
the template:

```json
[
    {
        "request": {
            "method": "GET",
            "path": "/v1/status"
        },
        "response": {
            "$template.content_response": {
                "data": {
                    "status": "ok",
                    "code": 200
                }
            }
        }
    }
]
```

At request time the `$template.content_response` key is removed and replaced with the expanded `content_response`
template, producing an ordinary `content` response with the body taken from the passed `data`.

- `$template.<name>` may appear **at any nesting level** — a `response`, a single `webhooks[]` item, or deeper.
- If `<name>` is not declared in `config.template`, the request fails with `500` (`Template not found: <name>`).

## Parameters

The value of the `$template.<name>` key becomes the template's parameter object, available inside the template body as
`template.*`:

| Placeholder        | Meaning                                                                    |
|--------------------|----------------------------------------------------------------------------|
| `{{template.key}}` | Whole value of `key`, **type preserved** (object, array, number, boolean). |
| `{template.key}`   | Inline interpolation into a surrounding string (value is cast to string).  |

Parameters are resolved together with the usual placeholders ([`env`](../../env.md), [`request`](../../request.md),
[`faker`](../../faker.md), [`date`](../../date.md)), so a template body may freely mix `{{template.*}}` with
`{env.*}`, `{{faker.*}}`, etc.

## Merging with sibling keys

An object may carry its own keys alongside `$template.<name>`. After the template is expanded, those sibling keys are
**merged recursively on top** of the template body — **mock data wins** on conflicts. This lets a template define
defaults while the mock overrides or extends per case:

```json
{
    "delay": 0.1,
    "headers": {
        "x-request-id": "{{request.headers.x-request-id}}"
    },
    "$template.order_webhook": {
        "type": "create_order",
        "data": "{{env.data}}"
    }
}
```

If `order_webhook` defines `headers.X-API-Key`, the resulting webhook keeps both `X-API-Key` (from the template) and
`x-request-id` (from the mock); `delay` from the mock is added as well.

## Complete example

Config — one template for the response, one for a webhook delivery:

```yaml
extensions:
  - class: Lav45\MockServer\Extension\Template\TemplateExtension
    config:
      template:
        order_response:
          type: "content"
          headers:
            content-type: "application/json"
          body: "{{template.data}}"
        order_webhook:
          url: "{env.WEBHOOK_URL}/publish"
          method: POST
          headers:
            X-API-Key: "{env.API_KEY}"
            content-type: "application/json"
          body:
            type: "{template.type}"
            data: "{{template.data}}"
```

Mock — the same `env.data` object is reused in the response and the webhook:

```json
[
    {
        "env": {
            "data": {
                "id": "{{faker.uuid}}",
                "createdAt": "{{date.getTimestamp()}}"
            }
        },
        "request": {
            "method": "POST",
            "path": "/v1/order"
        },
        "response": {
            "$template.order_response": {
                "data": "{{env.data}}"
            }
        },
        "webhooks": [
            {
                "delay": 0.1,
                "headers": {
                    "x-request-id": "{{request.headers.x-request-id}}"
                },
                "$template.order_webhook": {
                    "type": "create_order",
                    "data": "{{env.data}}"
                }
            }
        ]
    }
]
```

## Without templates

The same mock **without** the extension has to inline both template bodies; the values that used to come from server env
(`WEBHOOK_URL`, `API_KEY`) move into the mock's own `env` block:

```json
[
    {
        "env": {
            "WEBHOOK_URL": "https://api.example.com",
            "API_KEY": "secret-api-key",
            "data": {
                "id": "{{faker.uuid}}",
                "createdAt": "{{date.getTimestamp()}}"
            }
        },
        "request": {
            "method": "POST",
            "path": "/v1/order"
        },
        "response": {
            "type": "content",
            "headers": {
                "content-type": "application/json"
            },
            "body": "{{env.data}}"
        },
        "webhooks": [
            {
                "delay": 0.1,
                "url": "{env.WEBHOOK_URL}/publish",
                "method": "POST",
                "headers": {
                    "X-API-Key": "{env.API_KEY}",
                    "x-request-id": "{{request.headers.x-request-id}}",
                    "content-type": "application/json"
                },
                "body": {
                    "type": "create_order",
                    "data": "{{env.data}}"
                }
            }
        ]
    }
]
```

This repeats in **every** mock that needs the same response/webhook, and changing the publish URL, the API key, or the
payload shape means editing all of them — with a template it is a single change in `config.yaml`.

> `{{env.data}}` stays: that is the mock's own `env` block (reused across the response and the webhook), unrelated to
> the
> extension or to `config.yaml`.

## How it works

- The extension reads the matched mock data after routing and placeholder resolution.
- It walks the structure recursively; for every object that has a `$template.<name>` key it looks the template up in
  `config.template` (missing name → `500`), resolves `{{template.*}}` / `{template.*}` from the key's value, then merges
  the object's remaining keys on top with mock priority.
- The expanded structure replaces the original, so downstream extensions
  ([Content](response/content.md), [WebHooks](webhooks.md), [Proxy](response/proxy.md), …) see plain response/webhook
  definitions.
