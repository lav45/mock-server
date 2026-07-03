# Response type `content`

> Built-in [`Application`](../../application.md) extension — `Lav45\MockServer\Extension\Content\ContentExtension`.
> Enabled by default; registered in the `extensions` block of `etc/config.yaml` — comment that line out to disable it.

## `response.headers`

Response HTTP headers

| Types  | Default |
|--------|---------|
| object | `{}`    |

```json
[
    {
        "response": {
            "type": "content",
            "headers": {
                "content-type": "application/json"
            }
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
            "type": "content",
            "status": 200
        }
    }
]
```

## `response.body`

Response body — plain text string or JSON object/array.

| Types                 | Default |
|-----------------------|---------|
| string, array, object | `''`    |

```json
[
    {
        "response": {
            "type": "content",
            "body": "<html><body><h1>Hello world!</h1></body></html>"
        }
    }
]
```

```json
[
    {
        "response": {
            "type": "content",
            "body": {
                "status": "OK"
            }
        }
    }
]
```

## Request parameters

You can get data from your request and use it in the response:

- `params` - [Parameters](../../../request.md#requestpath) obtained from the path
- `query` - Query parameters from the request.
- `post` - Data from a POST HTTP request. Contains form data or body json data

Parentheses when describing the path to the parameter:

- `{` - The value will be inserted into the string as in a template
- `{{` - The value will be inserted without changing the data type. There can be only one value in the template, all
  other data will be erased.

```json
[
    {
        "request": {
            "method": [
                "GET",
                "POST"
            ],
            "path": "/request/{id:\\d+}"
        },
        "response": {
            "type": "content",
            "body": {
                "ID1": "ID: {request.query.id}",
                "ID2": "ID: {{request.query.id}}",
                "ID3": "ID: {request.body.id}",
                "ID4": "ID: {{request.body.id}}",
                "query": "{{request.query}}",
                "body": "{{request.body}}",
                "params": "{{request.params}}"
            }
        }
    }
]
```

```shell
curl -X POST 'http://127.0.0.1:8080/request/100?id=200' \
  --header 'Content-Type: application/json' \
  --data '{"id": 300}'
```

Response:

```json
{
    "ID1": "ID: 200",
    "ID2": "200",
    "ID3": "ID: 300",
    "ID4": 300,
    "query": {
        "id": "200"
    },
    "body": {
        "id": 300
    },
    "params": {
        "id": "100"
    }
}
```