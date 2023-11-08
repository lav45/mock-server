<?php declare(strict_types=1);

namespace lav45\MockServer\Mock\Response;

use lav45\MockServer\components\DTObject;

class Proxy extends DTObject
{
    public string $url;
    public array $options = [];
}