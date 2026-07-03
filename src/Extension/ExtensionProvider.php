<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension;

interface ExtensionProvider extends MiddlewareFactory
{
    public function type(): ExtensionType;
}
