# Response type `data`

## `response.delay`

Number of seconds to wait

| Types | Default |
|-------|---------|
| float | `0.0`   |

```json
[
    {
        "response": {
            "type": "data",
            "delay": 0.2
        }
    }
]
```

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

## `response.pagination.pageParam`

HTTP GET request name of the parameter storing the current page index.

| Types  | Default  |
|--------|----------|
| string | `"page"` |

## `response.pagination.pageSizeParam`

HTTP GET request name of the parameter storing the page size.

| Types  | Default      |
|--------|--------------|
| string | `"per-page"` |

## `response.pagination.defaultPageSize`

HTTP GET request the default page size.

| Types   | Default |
|---------|---------|
| integer | `20`    |

## `response.json`

An array of data in json format

| Types | Default |
|-------|---------|
| array | `[]`    |

```json
[
    {
        "response": {
            "type": "data",
            "json": [
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

The path to a file with an array of data in json format

The file contains a data set as from the example `response.json`

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

## Information parameters of pagination

- `response.pagination.totalItems` - total number of data items.
- `response.pagination.currentPage` - current page number (1-based).
- `response.pagination.totalPages` - total number of pages of
- `response.pagination.pageSize` - number of data items in each page.

## `response.result`

| Types  | Default                |
|--------|------------------------|
| string | `"{{response.items}}"` |

### Examples:

1) Pagination information in the headers

```json
[
    {
        "response": {
            "type": "data",
            "headers": {
                "X-Pagination-Total-Count": "{{response.pagination.totalItems}}",
                "X-Pagination-Current-Page": "{{response.pagination.currentPage}}",
                "X-Pagination-Page-Count": "{{response.pagination.totalPages}}",
                "X-Pagination-Per-Page": "{{response.pagination.pageSize}}"
            },
            "file": "/app/mocks/__data/file.json",
            "result": "{{response.items}}"
        }
    }
]
```

Response headers:

```
content-type: application/json
x-pagination-current-page: 2
x-pagination-page-count: 16
x-pagination-per-page: 2
x-pagination-total-count: 32
```

Response body:

```json
[
    {
        "id": "27b803dc-ceda-36f7-af87-5fbe2055ec0f",
        "name": "Danielle Lebsack"
    },
    {
        "id": "ee6e4d31-f6b2-3aad-a636-6e941ffbaa94",
        "name": "Dr. Verona Satterfield"
    }
]
```

2) Pagination information in the response data

```json
[
    {
        "response": {
            "type": "data",
            "file": "/app/mocks/__data/file.json",
            "result": {
                "data": "{{response.items}}",
                "pagination": "{{response.pagination}}"
            }
        }
    }
]
```

Response body:

```json
{
    "data": [
        {
            "id": "27b803dc-ceda-36f7-af87-5fbe2055ec0f",
            "name": "Danielle Lebsack"
        },
        {
            "id": "ee6e4d31-f6b2-3aad-a636-6e941ffbaa94",
            "name": "Dr. Verona Satterfield"
        }
    ],
    "pagination": {
        "totalItems": 32,
        "currentPage": 2,
        "totalPages": 16,
        "pageSize": 2
    }
}
```

