# Response type `content`

## `response.delay`

Number of seconds to wait

| Types | Default |
|-------|---------|
| float | `0.0`   |

```json
[
    {
        "response": {
            "type": "content",
            "delay": 0.2
        }
    }
]
```

## `response.headers`

Response HTTP headers

| Types  | Default |
|--------|---------|
| object | `[]`    |

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

## `response.text`

Response text content

| Types  | Default |
|--------|---------|
| string | `''`    |

```json
[
    {
        "response": {
            "type": "content",
            "text": "<html><body><h1>Hello world!</h1></body></html>"
        }
    }
]
```

## `response.json`

Response content in json format

| Types         | Default |
|---------------|---------|
| array, object | `null`  |

```json
[
    {
        "response": {
            "type": "content",
            "json": {
                "status": "OK"
            }
        }
    }
]
```

## Faker

You can use [Faker](https://fakerphp.github.io) to generate random data

```json
[
    {
        "response": {
            "type": "content",
            "json": {
                "id": "{{faker.uuid}}",
                "iban": "{{faker.iban('LV')}}",
                "time": "{{faker.dateTimeBetween('-1 week', '+1 week').getTimestamp()}}",
                "dateTime": "{{faker.dateTimeBetween('-1 week', '+1 week').format('d.m.Y H:i:s')}}",
                "flag": "{{faker.boolean}}",
                "location": "{{faker.localCoordinates()}}",
                "el": "{{faker.randomElements(['a', 'b', 'c'], 1, false)}}"
            }
        }
    }
]
```

Response:

```json
{
    "id": "ea6143fe-bf40-3f1a-90d3-e6872204888d",
    "iban": "LV89ORDR6OQ6J4G22N0T3",
    "time": 1676696670,
    "dateTime": "14.02.2023 08:20:34",
    "flag": true,
    "location": {
        "latitude": -39.658608,
        "longitude": 76.24428
    },
    "el": [
        "c"
    ]
}
```

## Request parameters

You can get data from your request and use it in the response:

- `urlParams` - [Parameters](../request.md#requesturl) obtained from the url
- `get` - Data from a GET HTTP request.
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
            "url": "/request/{id:\\d+}"
        },
        "response": {
            "type": "content",
            "json": {
                "ID1": "ID: {request.get.id}",
                "ID2": "ID: {{request.get.id}}",
                "ID3": "ID: {request.post.id}",
                "ID4": "ID: {{request.post.id}}",
                "get": "{{request.get}}",
                "post": "{{request.post}}",
                "urlParams": "{{request.urlParams}}"
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
    "get": {
        "id": "200"
    },
    "post": {
        "id": 300
    },
    "urlParams": {
        "id": "100"
    }
}
```