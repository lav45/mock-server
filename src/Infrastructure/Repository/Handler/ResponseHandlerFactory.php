<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Infrastructure\Parser\Parser;
use Psr\Log\LoggerInterface;

enum ResponseHandlerFactory: string
{
    case Content = ResponseContentHandler::TYPE;
    case Proxy = ResponseProxyHandler::TYPE;
    case Data = ResponseCollectionHandler::TYPE;

    public static function fromData(array $data): ResponseHandlerFactory
    {
        if (empty($data['response'])) {
            return self::Content;
        }
        if (isset($data['response']['type'])) {
            return self::from($data['response']['type']);
        }
        if (isset($data['response'][self::Content->value])) { // TODO deprecated
            return self::Content;
        }
        if (isset($data['response'][self::Proxy->value])) { // TODO deprecated
            return self::Proxy;
        }
        if (isset($data['response'][self::Data->value])) { // TODO deprecated
            return self::Data;
        }
        return self::Content;
    }

    public function create(Parser $parser, LoggerInterface $logger): Handler
    {
        return match ($this) {
            self::Content => new ResponseContentHandler($parser, $logger),
            self::Proxy => new ResponseProxyHandler($parser, $logger),
            self::Data => new ResponseCollectionHandler($parser, $logger),
        };
    }
}
