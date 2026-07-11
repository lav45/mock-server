# `data` — keyset navigation

> Applies when [`response.pagination.type`](../data.md) is `"keyset"`.

Cursor-based navigation over an in-memory dataset. Items are returned in the order they are defined in `items`/`file`
and sliced around the cursor carried by `after`/`before`. The cursor is the value of
[`primaryKey`](#responsepaginationprimarykey).

## `response.pagination.afterParam`

HTTP GET request name of the parameter holding the cursor of the **next** page (items *after* the cursor value).

| Types  | Default   |
|--------|-----------|
| string | `"after"` |

## `response.pagination.beforeParam`

HTTP GET request name of the parameter holding the cursor of the **previous** page (items *before* the cursor value).

| Types  | Default    |
|--------|------------|
| string | `"before"` |

## `response.pagination.limitParam`

HTTP GET request name of the parameter storing the page size.

| Types  | Default   |
|--------|-----------|
| string | `"limit"` |

## `response.pagination.defaultPageSize`

Default page size when the request omits the `limit` parameter.

| Types   | Default |
|---------|---------|
| integer | `20`    |

## `response.pagination.primaryKey`

The unique field used as the cursor. Its value is what `after`/`before` carry. It must be present in every item and
unique across the dataset — a `uuid` `id` is a good fit.

| Types  | Default |
|--------|---------|
| string | `"id"`  |

## Information parameters

- `response.pagination.next` - cursor value for the next page (`null` on the last page).
- `response.pagination.prev` - cursor value for the previous page (`null` on the first page).
- `response.pagination.hasNext` - whether a next page exists.
- `response.pagination.hasPrev` - whether a previous page exists.
- `response.pagination.pageSize` - number of data items in the current page.

An unknown `after`/`before` cursor (missing from the dataset) is treated as the first page.

## Pagination in the headers

Response headers are strings, so the boolean fields `hasNext`/`hasPrev` are rendered differently depending on the
braces:

- `{...}` → `"true"` / `"false"`
- `{{...}}` → `"1"` / `""`

```json
[
    {
        "response": {
            "type": "data",
            "headers": {
                "X-Next-Cursor": "{response.pagination.next}",
                "X-Has-Next": "{response.pagination.hasNext}",
                "X-Has-Prev": "{response.pagination.hasPrev}"
            },
            "pagination": {
                "type": "keyset",
                "primaryKey": "id"
            },
            "file": "/app/mocks/__data/file.json",
            "result": "{{response.items}}"
        }
    }
]
```

Response headers on the first page:

```
x-has-next: true
x-has-prev: false
x-next-cursor: 537b0bc3-57c2-383b-8819-040dc731963f
```

## Example (`before`/`after`)

Items are returned in file order; the `uuid` `id` is the cursor:

```json
[
    {
        "response": {
            "type": "data",
            "pagination": {
                "type": "keyset",
                "primaryKey": "id",
                "defaultPageSize": 2
            },
            "file": "/app/mocks/__data/file.json",
            "result": {
                "data": "{{response.items}}",
                "pagination": "{{response.pagination}}"
            }
        }
    }
]
```

Request the next page after the cursor returned earlier:

```
GET /data?after=27b803dc-ceda-36f7-af87-5fbe2055ec0f&limit=2
```

Response body:

```json
{
    "data": [
        {"id": "ee6e4d31-f6b2-3aad-a636-6e941ffbaa94", "created_at": "2024-01-08"},
        {"id": "537b0bc3-57c2-383b-8819-040dc731963f", "created_at": "2024-01-07"}
    ],
    "pagination": {
        "next": "537b0bc3-57c2-383b-8819-040dc731963f",
        "prev": "ee6e4d31-f6b2-3aad-a636-6e941ffbaa94",
        "hasNext": true,
        "hasPrev": true,
        "pageSize": 2
    }
}
```

To go one page back, feed `prev` into `before`:

```
GET /data?before=ee6e4d31-f6b2-3aad-a636-6e941ffbaa94&limit=2
```
