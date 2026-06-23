<?php declare(strict_types=1);

namespace Lav45\MockServer\Engine\Http;

final class ServerResponse
{
    private const array REASON_PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        415 => 'Unsupported Media Type',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ];

    private int $status;

    private string $reason;

    /** @var array<string, string|list<string>> */
    private array $headers;

    private string $body;

    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(int $status = 200, array $headers = [], string $body = '')
    {
        $this->status = $status;
        $this->reason = self::REASON_PHRASES[$status] ?? '';
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status, string|null $reason = null): void
    {
        $this->status = $status;
        $this->reason = $reason ?? self::REASON_PHRASES[$status] ?? '';
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @return array<string, string|list<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): string|null
    {
        $value = $this->headers[$name] ?? null;
        if (\is_array($value)) {
            return $value[0] ?? null;
        }
        return $value;
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }
}
