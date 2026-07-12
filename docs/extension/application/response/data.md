# Response type `data`

> Built-in [`Application`](../../application.md) extension — `Lav45\MockServer\Extension\Collection\CollectionExtension`.
> Enabled by default; registered in the `extensions` block of `etc/config.yaml` — comment that line out to disable it.

## `response.status`

Response HTTP status code

| Types   | Default |
|---------|---------|
| integer | `200`   |

```json
[
    {
        "response": {
            "type": "data",
            "status": 200
        }
    }
]
```

## `response.headers`

Response HTTP headers

| Types  | Default                                |
|--------|----------------------------------------|
| object | `{"content-type": "application/json"}` |

```json
[
    {
        "response": {
            "type": "data",
            "headers": {
                "content-type": "application/json"
            }
        }
    }
]
```

> **Note:** The response body is always JSON, so `content-type: application/json` is added automatically unless already set.

## `response.pagination.type`

Pagination strategy. Each strategy has its own parameters, information fields and examples:

- [`"offset"`](data/offset.md) — classic page-based pagination driven by the `page`/`per-page` query parameters. Knows
  the total number of items and pages. **Default.**
- [`"keyset"`](data/keyset.md) — cursor-based navigation driven by the `after`/`before` query parameters. Scales to large
  datasets and keeps a stable window while items are inserted or deleted, but does not know the total number of items or
  pages.
- [`"iterator"`](data/iterator.md) — keyset navigation driven by a single signed `iterator` parameter (sign is the
  direction, magnitude is the cursor).

| Types  | Default    |
|--------|------------|
| string | `"offset"` |

```json
[
    {
        "response": {
            "type": "data",
            "pagination": {
                "type": "keyset"
            }
        }
    }
]
```

## `response.items`

An array of data in JSON format

| Types | Default |
|-------|---------|
| array | `[]`    |

```json
[
    {
        "response": {
            "type": "data",
            "items": [
                {
                    "id": "537b0bc3-57c2-383b-8819-040dc731963f",
                    "name": "Dana Kilback"
                },
                {
                    "id": "{{faker.uuid}}",
                    "name": "{{faker.name}}"
                }
            ]
        }
    }
]
```

## `response.file`

The path to a file with an array of data in JSON format

The file contains a data set as from the example `response.items`

| Types  | Default |
|--------|---------|
| string | `null`  |

```json
[
    {
        "response": {
            "type": "data",
            "file": "/app/mocks/__data/file.json"
        }
    }
]
```

## `response.result`

Template of the response body. Defaults to the raw items array; combine it with `{{response.pagination}}` to expose the
pagination metadata. See the worked examples in the [offset](data/offset.md) and [keyset](data/keyset.md) docs.

| Types  | Default                |
|--------|------------------------|
| string | `"{{response.items}}"` |
