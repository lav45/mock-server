<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Data\Mock\v1;

use Sunrise\Hydrator\Annotation\Subtype;

/**
 * @codeCoverageIgnore
 */
final readonly class Mock
{
    public function __construct(
        public Request  $request,
        public Response $response,
        /** @var Webhook[] */
        #[Subtype(Webhook::class)]
        public array    $webhooks = [],
        public array    $env = [],
    ) {}
}
