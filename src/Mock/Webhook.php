<?php declare(strict_types=1);

namespace lav45\MockServer\Mock;

use lav45\MockServer\Component\DTObject;

class Webhook extends DTObject
{
    public float|string $delay = 0;
    public string $method = 'POST';
    public string $url;
    public array $headers = [];
    public array $json = [];
    public string $text = '';
    /** @deprecated */
    public array $options = [];
}