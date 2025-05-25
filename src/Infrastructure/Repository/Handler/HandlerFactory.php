<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Infrastructure\Parser\Parser;

enum HandlerFactory: string
{
    case Content = ResponseContentHandler::TYPE;
    case Proxy = ResponseProxyHandler::TYPE;
    case Data = ResponseCollectionHandler::TYPE;

    public static function fromData(array $data): self
    {
        if (isset($data['response']['type'])) {
            return self::from($data['response']['type']);
        }
        return self::Content;
    }

    public function create(Parser $parser): Handler
    {
        return match ($this) {
            self::Content => new ResponseContentHandler($parser),
            self::Proxy => new ResponseProxyHandler($parser),
            self::Data => new ResponseCollectionHandler($parser),
        };
    }
}
