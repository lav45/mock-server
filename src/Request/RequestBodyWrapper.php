<?php declare(strict_types=1);

namespace lav45\MockServer\Request;

use Amp\Cancellation;
use Amp\Http\Server\RequestBody;
use RuntimeException;
use const PHP_INT_MAX;

/**
 * @mixin RequestBody
 */
class RequestBodyWrapper
{
    protected RequestBody $body;
    protected ?string $buffer = null;
    protected ?string $read = null;

    public function __construct(RequestBody $requestBody)
    {
        $this->body = $requestBody;
    }

    public function __call(string $name, array $args)
    {
        if (method_exists($this->body, $name)) {
            return call_user_func_array([$this->body, $name], $args);
        }
        throw new RuntimeException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    public function buffer(?Cancellation $cancellation = null, int $limit = PHP_INT_MAX): string
    {
        return $this->buffer ??= $this->body->buffer($cancellation, $limit);
    }

    public function read(?Cancellation $cancellation = null): ?string
    {
        return $this->read ??= $this->body->read($cancellation);
    }
}
