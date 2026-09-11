<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClientInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamException;
use Setono\SyliusVideoPlugin\CloudflareStream\DirectUpload;
use Setono\SyliusVideoPlugin\Controller\Admin\CloudflareStreamDirectUploadAction;
use Symfony\Component\HttpFoundation\Request;

final class CloudflareStreamDirectUploadActionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_creates_a_direct_upload_and_returns_its_url_and_uid(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->createDirectUpload(1234, 'clip.mp4')->willReturn(new DirectUpload('https://upload.cloudflarestream.com/abc', 'video123'));

        $response = (new CloudflareStreamDirectUploadAction($client->reveal()))($this->request(['name' => ' clip.mp4 ', 'size' => 1234]));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['uploadUrl' => 'https://upload.cloudflarestream.com/abc', 'uid' => 'video123'], json_decode((string) $response->getContent(), true));
    }

    /**
     * @test
     */
    public function it_refuses_a_request_that_is_not_an_xml_http_request(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->createDirectUpload(Argument::cetera())->shouldNotBeCalled();

        $response = (new CloudflareStreamDirectUploadAction($client->reveal()))($this->request(['name' => 'clip.mp4', 'size' => 1234], false));

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['error' => 'This endpoint only accepts XMLHttpRequest calls.'], json_decode((string) $response->getContent(), true));
    }

    /**
     * @test
     *
     * @dataProvider invalidPayloads
     */
    public function it_refuses_an_invalid_payload(string $body): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->createDirectUpload(Argument::cetera())->shouldNotBeCalled();

        $response = (new CloudflareStreamDirectUploadAction($client->reveal()))($this->rawRequest($body));

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['error' => 'Expected a JSON body with a non-empty "name" and a positive integer "size".'], json_decode((string) $response->getContent(), true));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'not json' => ['nope'];
        yield 'empty object' => ['{}'];
        yield 'blank name' => ['{"name":"  ","size":10}'];
        yield 'name not a string' => ['{"name":5,"size":10}'];
        yield 'size missing' => ['{"name":"clip.mp4"}'];
        yield 'size zero' => ['{"name":"clip.mp4","size":0}'];
        yield 'size negative' => ['{"name":"clip.mp4","size":-1}'];
        yield 'size a string' => ['{"name":"clip.mp4","size":"10"}'];
    }

    /**
     * @test
     */
    public function it_reports_a_cloudflare_failure_as_a_bad_gateway_and_logs_it(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->createDirectUpload(1234, 'clip.mp4')->willThrow(new CloudflareStreamException('Authentication error'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::containingString('Could not create a Cloudflare Stream direct upload'), Argument::withEntry('name', 'clip.mp4'))->shouldBeCalledOnce();

        $response = (new CloudflareStreamDirectUploadAction($client->reveal(), $logger->reveal()))($this->request(['name' => 'clip.mp4', 'size' => 1234]));

        self::assertSame(502, $response->getStatusCode());
        self::assertSame(['error' => 'Authentication error'], json_decode((string) $response->getContent(), true));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function request(array $payload, bool $xmlHttpRequest = true): Request
    {
        return $this->rawRequest((string) json_encode($payload), $xmlHttpRequest);
    }

    private function rawRequest(string $body, bool $xmlHttpRequest = true): Request
    {
        $request = Request::create('/admin/videos/cloudflare-stream/direct-upload', 'POST', [], [], [], [], $body);

        if ($xmlHttpRequest) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        return $request;
    }
}
