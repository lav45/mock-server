<?php

namespace lav45\MockServer\Request;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Cancellation;
use Amp\Http\Server\ClientException;
use Amp\Http\Server\RequestBody;
use RuntimeException;
use const PHP_INT_MAX;

/**
 * @mixin RequestBody
 */
class WrappedRequestBody
{
    /** @var RequestBody */
    protected RequestBody $body;
    /** @var string|null */
    protected ?string $buffer = null;

    /**
     * @param RequestBody $requestBody
     */
    public function __construct(RequestBody $requestBody)
    {
        $this->body = $requestBody;
    }

    /**
     * @param string $name
     * @param array $args
     * @return false|mixed
     */
    public function __call(string $name, array $args)
    {
        if (method_exists($this->body, $name)) {
            return call_user_func_array([$this->body, $name], $args);
        }
        throw new RuntimeException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    /**
     * @param Cancellation|null $cancellation
     * @param int $limit
     * @return string
     * @throws BufferException
     * @throws StreamException
     * @throws ClientException
     */
    public function buffer(?Cancellation $cancellation = null, int $limit = PHP_INT_MAX): string
    {
        return $this->buffer ??= $this->body->buffer($cancellation, $limit);
    }

    /**
     * @param Cancellation|null $cancellation
     * @return string|null
     * @throws StreamException
     * @throws ClientException
     */
    public function read(?Cancellation $cancellation = null): ?string
    {
        return $this->buffer ??= $this->body->read($cancellation);
    }
}
