# `data` — iterator navigation

> Applies when [`response.pagination.type`](../data.md) is `"iterator"`.

Works like [keyset](keyset.md) navigation, but both direction and cursor are carried by a **single** `iterator`
parameter: the sign is the direction, the magnitude is the [`primaryKey`](#responsepaginationprimarykey) cursor value.

- `?iterator=10&limit=20` — the next `20` items **after** the item with `primaryKey` `10`.
- `?iterator=-10` — the previous page, i.e. items **before** the item with `primaryKey` `10`.
- No `iterator` — the first page.

Because the sign encodes the direction, `primaryKey` values are expected to be non-negative.

## `response.pagination.iteratorParam`

HTTP GET request name of the single navigation parameter.

| Types  | Default      |
|--------|--------------|
| string | `"iterator"` |

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

The unique field used as the cursor. Its value is what `iterator` carries. It must be present in every item and unique
across the dataset.

| Types  | Default |
|--------|---------|
| string | `"id"`  |

## Information parameters

- `response.pagination.next` - value to pass as `?iterator=` for the next page (`null` on the last page).
- `response.pagination.prev` - value to pass as `?iterator=` for the previous page, already signed (`null` on the first
  page).
- `response.pagination.hasNext` - whether a next page exists.
- `response.pagination.hasPrev` - whether a previous page exists.
- `response.pagination.pageSize` - number of data items in the current page.

An unknown `iterator` cursor (missing from the dataset) is treated as the first page.

## Example

```json
[
    {
        "response": {
            "type": "data",
            "pagination": {
                "type": "iterator",
                "primaryKey": "id",
                "defaultPageSize": 2
            },
            "items": [
                {"id": 60}, {"id": 50}, {"id": 40},
                {"id": 30}, {"id": 20}, {"id": 10}
            ],
            "result": {
                "data": "{{response.items}}",
                "pagination": "{{response.pagination}}"
            }
        }
    }
]
```

The next page after item `50`:

```
GET /data?iterator=50&limit=2
```

Response body:

```json
{
    "data": [
        {"id": 40},
        {"id": 30}
    ],
    "pagination": {
        "next": "30",
        "prev": "-40",
        "hasNext": true,
        "hasPrev": true,
        "pageSize": 2
    }
}
```

Feed `prev` straight back to go one page back:

```
GET /data?iterator=-40&limit=2
```
