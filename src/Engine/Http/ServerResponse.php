<?php declare(strict_types=1);

namespace Lav45\MockServer\Engine\Http;

use Lav45\MockServer\Domain\ValueObject\Body;

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

    private string $reason;

    private Body $body;

    public function __construct(
        private int   $status = 200,
        /** @var array<string, string|list<string>> */
        private array $headers = [],
        Body|null     $body = null,
    ) {
        $this->reason = self::REASON_PHRASES[$status] ?? '';
        $this->body = $body ?? Body::new('');
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

    public function getBody(): Body
    {
        return $this->body;
    }

    public function setBody(Body $body): void
    {
        $this->body = $body;
    }
}
