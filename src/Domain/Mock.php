<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain;

use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Domain\Mock\WebHooks;

final readonly class Mock
{
    public function __construct(
        public Response $response,
        public WebHooks $webHooks,
    ) {}
}
