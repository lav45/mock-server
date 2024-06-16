<?php declare(strict_types=1);

namespace lav45\MockServer\Domain\ValueObject\Response;

final readonly class HttpStatus
{
    public function __construct(public int $value)
    {
        \assert($this->isValidStatus($value), 'Invalid status');
    }

    private function isValidStatus(int $status): bool
    {
        return
            $this->isInformational($status) ||
            $this->isSuccessful($status) ||
            $this->isRedirect($status) ||
            $this->isClientError($status) ||
            $this->isServerError($status);
    }

    /**
     * Status code is between 100 and 199, representing an informational response.
     */
    public function isInformational(int $code): bool
    {
        return $code >= 100 && $code < 200;
    }

    /**
     * Status code is between 200 and 299, representing a successful response.
     */
    public function isSuccessful(int $code): bool
    {
        return $code >= 200 && $code < 300;
    }

    /**
     * Status code is between 300 and 399, representing a redirect response.
     */
    public function isRedirect(int $code): bool
    {
        return $code >= 300 && $code < 400;
    }

    /**
     * Status code is between 400 and 499, representing a client error response.
     */
    public function isClientError(int $code): bool
    {
        return $code >= 400 && $code < 500;
    }

    /**
     * Status code is between 500 and 599, representing a server error response.
     */
    public function isServerError(int $code): bool
    {
        return $code >= 500 && $code < 600;
    }
}
