<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Data\Mock\v1\Response;

use Lav45\MockServer\Application\Data\Mock\v1\Response\Data\Pagination;

/**
 * @codeCoverageIgnore
 */
final readonly class Data
{
    public function __construct(
        public Pagination  $pagination,
        public mixed       $status = 200,
        public array       $headers = [],
        public mixed       $result = '{{response.data.items}}',
        public array       $json = [],
        public string|null $file = null,
    ) {}
}
