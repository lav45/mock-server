<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Content;

use Lav45\MockServer\Domain\Response\ContentResponse;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpStatus;
use Lav45\MockServer\Extension\Content\ContentResponder;
use PHPUnit\Framework\TestCase;

final class ContentResponderTest extends TestCase
{
    public function testReturnsCorrectStatus(): void
    {
        $data = new ContentResponse(
            status: new HttpStatus(201),
            headers: HttpHeaders::fromArray([]),
            body: Body::new(''),
        );

        $response = new ContentResponder()->execute($data);

        $this->assertSame(201, $response->getStatus());
    }

    public function testReturnsCorrectBody(): void
    {
        $data = new ContentResponse(
            status: new HttpStatus(200),
            headers: HttpHeaders::fromArray([]),
            body: Body::new('hello world'),
        );

        $response = new ContentResponder()->execute($data);

        $this->assertSame('hello world', $response->getBody()->stream->read());
    }

    public function testReturnsCorrectHeaders(): void
    {
        $data = new ContentResponse(
            status: new HttpStatus(200),
            headers: HttpHeaders::fromArray(['content-type' => 'application/json', 'x-custom' => 'value']),
            body: Body::new(''),
        );

        $response = new ContentResponder()->execute($data);

        $this->assertSame('application/json', $response->getHeader('content-type'));
        $this->assertSame('value', $response->getHeader('x-custom'));
    }
}
