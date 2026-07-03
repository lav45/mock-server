# Delay

> Built-in [`Application`](../application.md) extension — `Lav45\MockServer\Extension\Throttling\ThrottlingExtension`.
> Enabled by default; registered in the `extensions` block of `etc/config.yaml` — comment that line out to disable it.

Delays the response by `response.delay` seconds. It is **not tied to any response type** — any mock whose `response`
carries a `delay` is throttled, whether it is `content`, `proxy`, `data`, `direct` or a custom type. Nothing beyond
`response.delay` is required.

## `response.delay`

Number of seconds to wait before the response is sent.

| Types | Default |
|-------|---------|
| float | `0.0`   |

```json
[
    {
        "response": {
            "delay": 0.2
        }
    }
]
```

The value may also be a template that resolves to a number — for example a reference to an [`env`](../../env.md) value,
so the delay can be tuned without editing the mock:

```json
[
    {
        "env": {
            "delay": 0.2
        },
        "response": {
            "delay": "{{env.delay}}"
        }
    }
]
```

`delay` is the **total minimum response time**, not an extra wait added on top of it: the extension measures how long
generating the response took and sleeps only the remainder. If producing the response already took longer than `delay`,
no extra waiting happens.
