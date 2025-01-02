<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Response;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Lav45\MockServer\Infrastructure\Repository\Factory\BodyFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\DelayFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\HeadersFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\StatusFactory;
use Psr\Log\LoggerInterface;

final readonly class ResponseContentHandler implements Handler
{
    public const string TYPE = 'content';

    public function __construct(
        private Parser          $parser,
        private LoggerInterface $logger,
    ) {}

    private function getData(array $data): array
    {
        $response = ArrayHelper::getValue($data, 'response', []);
        if (isset($response['type']) && $response['type'] === self::TYPE) {
            return $response;
        }
        if (isset($response[self::TYPE])) { // TODO deprecated
            $this->logger->info("Data:\n" . \json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $this->logger->warning("Option `response." . self::TYPE . "` is deprecated, you can use `response.type` = '" . self::TYPE . "' or run `upgrade` script.");

            $result = $response[self::TYPE];
            if (isset($response['delay'])) {
                $result['delay'] = $response['delay'];
            }
            return $result;
        }
        return $response;
    }

    public function handle(array $data, Request $request): Response
    {
        $data = $this->getData($data);

        $start = new Response\Start($request->start);

        $delay = (new DelayFactory($this->parser))->create($data, 'delay');

        $status = (new StatusFactory($this->parser))->create($data, 'status');

        $headers = (new HeadersFactory(
            parser: $this->parser,
            logger: $this->logger,
            withJson: isset($data['json']),
        ))->create(
            data: $data,
            path: 'headers',
        );

        $body = (new BodyFactory($this->parser))->fromContent($data, 'text', 'json');

        return new Response\Content(
            start: $start,
            delay: $delay,
            status: $status,
            headers: $headers,
            body: $body,
        );
    }
}
