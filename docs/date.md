# Date

`{{date.*}}` resolves to the current date and time. `date` is a PHP
[`\DateTime`](https://www.php.net/manual/en/class.datetime.php) instance for the moment the request is handled (with
microsecond precision) — call any of its methods straight from the template.

| Template                   | Result                                                                                                     |
|----------------------------|------------------------------------------------------------------------------------------------------------|
| `{{date.getTimestamp()}}`  | Unix timestamp                                                                                             |
| `{{date.format('Y-m-d')}}` | Formatted string — see the PHP [date format](https://www.php.net/manual/en/datetime.format.php) characters |

Method arguments are written in single quotes, e.g. `{{date.format('Y-m-d\TH:i:s.u\Z')}}`.

Double braces `{{date.*}}` coerce the value to its native type — `getTimestamp()` becomes a number. Single braces
`{date.*}` interpolate the value as a string inline. Like [`{{faker.*}}`](faker.md), it works anywhere templates are
resolved — [`env`](env.md), a [response](extension/application/response.md) body, a
[webhook](extension/application/webhooks.md) body, [conditions](extension/application/conditions.md) or a
[direct](extension/application/direct.md) reply.

## Example

```json
[
    {
        "response": {
            "type": "content",
            "body": {
                "timestamp": "{{date.getTimestamp()}}",
                "date": "{{date.format('Y-m-d')}}",
                "dateTime": "{{date.format('Y-m-d H:i:s')}}",
                "iso": "{{date.format('c')}}",
                "text": "generated at {date.format('Y-m-d')}"
            }
        }
    }
]
```

Response:

```json
{
    "timestamp": 1676696670,
    "date": "2023-02-18",
    "dateTime": "2023-02-18 04:24:55",
    "iso": "2023-02-18T04:24:55+00:00",
    "text": "generated at 2023-02-18"
}
```

`timestamp` arrives as a number because it uses double braces; `text` stays a string thanks to single-brace
interpolation.
