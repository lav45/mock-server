# Conditions

`conditions` is an optional array of conditional overrides evaluated before the default [response](response.md).
Each condition defines a `match` expression and a `response` (and optionally `webhooks`) to use when the match succeeds.
Conditions are checked in order — the **first match wins**. If none match, the root-level `response` is used as a
fallback.

```json
[
    {
        "request": {
            "method": "POST",
            "path": "/api/payment"
        },
        "conditions": [
            {
                "match": [">", "{{request.body.amount}}", 1000],
                "response": {
                    "status": 402,
                    "body": {
                        "error": "limit exceeded"
                    }
                }
            }
        ],
        "response": {
            "status": 200,
            "body": {
                "result": "ok"
            }
        }
    }
]
```

---

## `match`

A `match` is an **expression** — either a single condition or a logical combination of conditions:

```
expr = [operator, "{{field}}", value]
      | ["exists", "{{field}}"]
      | ["and", expr, expr, ...]
      | ["or", expr, expr, ...]
      | ["not", expr]
```

Every expression starts with the operator. For comparisons the field comes second and the value third; the logical
operators (`and`/`or`/`not`) and the unary `exists`/`empty` follow the same operator-first shape.

### Field references

A field is written as a template `"{{...}}"` — the same syntax used everywhere else in a mock. It is resolved against
the incoming request (and `env`) at match time; if it resolves to nothing, the field is treated as absent.

| Field                        | What it targets                                             |
|------------------------------|-------------------------------------------------------------|
| `{{request.body.<key>}}`     | Parsed request body (JSON, form-encoded, plain text)        |
| `{{request.query.<key>}}`    | URL query parameters                                        |
| `{{request.headers.<name>}}` | Request headers (case-insensitive names)                    |
| `{{request.params.<name>}}`  | Path parameters captured by the route pattern (`{id}`, etc) |
| `{{request.method}}`         | HTTP method string (`GET`, `POST`, …)                       |
| `{{request.path}}`           | Full request path string (e.g. `/api/users/42`)             |
| `{{env.<key>}}`              | Value from the mock's `env` block                           |

Keys in `request.body` and `request.query` support dot-notation for nested properties: `"{{request.body.user.address.city}}"`.

### Values

A condition value is a JSON literal (`string`, `number`, `boolean`, `null`, `array`).
Use template syntax to reference dynamic values:

| Source | Example            |
|--------|--------------------|
| env    | `"{{env.limit}}"`  |
| faker  | `"{{faker.uuid}}"` |

### Operators

| Operator   | Description                                      | Syntax                               |
|------------|--------------------------------------------------|--------------------------------------|
| `=`        | Strict equality                                  | `["=", "{{field}}", value]`          |
| `!=`       | Not equal                                        | `["!=", "{{field}}", value]`         |
| `>`        | Greater than                                     | `[">", "{{field}}", number]`         |
| `>=`       | Greater than or equal                            | `[">=", "{{field}}", number]`        |
| `<`        | Less than                                        | `["<", "{{field}}", number]`         |
| `<=`       | Less than or equal                               | `["<=", "{{field}}", number]`        |
| `contains` | Substring present / array includes value         | `["contains", "{{field}}", string]`  |
| `~`        | Regex match (no leading/trailing slashes needed) | `["~", "{{field}}", regex]`          |
| `in`       | Value is one of the listed items                 | `["in", "{{field}}", [v1, v2, ...]]` |
| `exists`   | Field is present                                 | `["exists", "{{field}}"]`            |
| `empty`    | Value is `null` or empty string                  | `["empty", "{{field}}"]`             |

To negate any expression, wrap it in `["not", ...]`:

```json
[
    ["not", ["contains", "{{request.body.tag}}", "foo"]],
    ["not", ["~", "{{request.params.id}}", "^test-"]],
    ["not", ["in", "{{request.body.currency}}", ["USD", "EUR"]]]
]
```

**Field presence and emptiness** use the standard operators:

| Intent                      | Expression                         |
|-----------------------------|------------------------------------|
| Field is present            | `["exists", "{{field}}"]`          |
| Field is absent             | `["not", ["exists", "{{field}}"]]` |
| Value is `null` or `""`     | `["empty", "{{field}}"]`           |
| Value is not `null` or `""` | `["not", ["empty", "{{field}}"]]`  |

---

## `response`

Follows exactly the same format as the root-level [response](response.md).
Fully replaces the root response when the condition matches — including status, headers, body, delay, and type.
Template helpers (`{{request.params.id}}`, `{{env.fee}}`, etc.) work the same way.

## `webhooks`

Optional. Follows the same format as the root-level [webhooks](webhooks.md).
Fully replaces the root webhooks when the condition matches.
Set to an empty array `[]` to suppress all webhooks for this condition.
Omit the key entirely to fall back to the root-level `webhooks`.

---

## Examples

### Amount and currency check (AND)

```json
[
    {
        "conditions": [
            {
                "match": ["and",
                    [">", "{{request.body.amount}}", 1000],
                    ["in", "{{request.body.currency}}", ["USD", "EUR"]],
                    ["!=", "{{request.headers.x-role}}", "premium"]
                ],
                "response": {
                    "status": 402,
                    "body": {
                        "error": "limit exceeded"
                    }
                }
            }
        ]
    }
]
```

### Dry-run via query or header (OR)

```json
[
    {
        "conditions": [
            {
                "match": ["or",
                    ["=", "{{request.query.dry_run}}", "true"],
                    ["exists", "{{request.headers.x-dry-run}}"]
                ],
                "response": {
                    "status": 200,
                    "body": {
                        "result": "ok_dry_run",
                        "fee": 0
                    }
                },
                "webhooks": []
            }
        ]
    }
]
```

### Route param prefix check

```json
[
    {
        "conditions": [
            {
                "match": ["~", "{{request.params.id}}", "^test-"],
                "response": {
                    "status": 200,
                    "body": {
                        "result": "test_mode"
                    }
                }
            }
        ]
    }
]
```

### Nested body path

```json
[
    {
        "conditions": [
            {
                "match": ["=", "{{request.body.user.address.city}}", "Munichen"],
                "response": {
                    "status": 200,
                    "body": {
                        "shipping": "ru"
                    }
                }
            }
        ]
    }
]
```

### Comparing against env values

```json
[
    {
        "env": {
            "limit": 1000
        },
        "conditions": [
            {
                "match": [">", "{{request.body.amount}}", "{{env.limit}}"],
                "response": {
                    "status": 402,
                    "body": {
                        "error": "limit exceeded"
                    }
                }
            }
        ]
    }
]
```

### Nested AND/OR logic

Logical operators compose freely — nest them to any depth:

```json
[
    {
        "conditions": [
            {
                "match": ["or",
                    ["and",
                        [">", "{{request.body.amount}}", 1000],
                        ["in", "{{request.body.currency}}", ["USD", "EUR"]]
                    ],
                    ["and",
                        ["!=", "{{request.headers.x-role}}", "premium"],
                        ["=", "{{request.headers.x-banned}}", "true"]
                    ]
                ],
                "response": {
                    "status": 402,
                    "body": {
                        "error": "limit exceeded"
                    }
                }
            }
        ]
    }
]
```

Reads as: `(request.body.amount > 1000 AND request.body.currency IN [USD, EUR]) OR (request.headers.x-role != premium AND request.headers.x-banned = true)`.

---

### Full example with fallback

```json
[
    {
        "request": {
            "method": "POST",
            "path": "/api/payment/{id}"
        },
        "env": {
            "fee": 0.05
        },
        "conditions": [
            {
                "match": ["and",
                    [">", "{{request.body.amount}}", 1000],
                    ["in", "{{request.body.currency}}", ["USD", "EUR"]],
                    ["!=", "{{request.headers.x-role}}", "premium"]
                ],
                "response": {
                    "status": 402,
                    "body": {
                        "error": "limit exceeded"
                    }
                }
            },
            {
                "match": ["or",
                    ["=", "{{request.query.dry_run}}", "true"],
                    ["exists", "{{request.headers.x-dry-run}}"]
                ],
                "response": {
                    "status": 200,
                    "body": {
                        "result": "ok_dry_run"
                    }
                },
                "webhooks": []
            },
            {
                "match": ["~", "{{request.params.id}}", "^test-"],
                "response": {
                    "status": 200,
                    "body": {
                        "result": "test_mode"
                    }
                }
            }
        ],
        "response": {
            "status": 200,
            "body": {
                "result": "ok",
                "fee": "{{env.fee}}"
            }
        },
        "webhooks": [
            {
                "url": "https://example.com/hook",
                "body": {
                    "event": "payment"
                }
            }
        ]
    }
]
```