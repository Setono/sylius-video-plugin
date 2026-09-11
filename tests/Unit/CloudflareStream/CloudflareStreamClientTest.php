<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\CloudflareStream;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClient;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CloudflareStreamClientTest extends TestCase
{
    /** @var list<array{method: string, url: string, headers: array<string, string>}> */
    private array $requests = [];

    /**
     * @test
     */
    public function it_creates_a_direct_upload_over_tus(): void
    {
        $client = $this->client([new MockResponse('', [
            'http_code' => 201,
            'response_headers' => [
                'Location' => 'https://upload.cloudflarestream.com/abc',
                'stream-media-id' => 'video123',
            ],
        ])], maxDurationSeconds: 3600);

        $upload = $client->createDirectUpload(1234, 'clip.mp4');

        self::assertSame('https://upload.cloudflarestream.com/abc', $upload->uploadUrl);
        self::assertSame('video123', $upload->uid);

        self::assertCount(1, $this->requests);
        self::assertSame('POST', $this->requests[0]['method']);
        self::assertSame('https://api.cloudflare.com/client/v4/accounts/acc/stream?direct_user=true', $this->requests[0]['url']);
        self::assertSame('Bearer token', $this->requests[0]['headers']['authorization']);
        self::assertSame('1.0.0', $this->requests[0]['headers']['tus-resumable']);
        self::assertSame('1234', $this->requests[0]['headers']['upload-length']);
        self::assertSame('name ' . base64_encode('clip.mp4') . ',maxDurationSeconds ' . base64_encode('3600'), $this->requests[0]['headers']['upload-metadata']);
    }

    /**
     * @test
     */
    public function it_omits_the_maximum_duration_when_none_is_configured(): void
    {
        $client = $this->client([new MockResponse('', [
            'http_code' => 201,
            'response_headers' => ['Location' => 'https://upload.cloudflarestream.com/abc', 'stream-media-id' => 'video123'],
        ])]);

        $client->createDirectUpload(10, 'clip.mp4');

        self::assertSame('name ' . base64_encode('clip.mp4'), $this->requests[0]['headers']['upload-metadata']);
    }

    /**
     * @test
     */
    public function it_uses_the_configured_api_base_url(): void
    {
        $client = new CloudflareStreamClient($this->httpClient([new MockResponse('', [
            'http_code' => 201,
            'response_headers' => ['Location' => 'https://upload.example.com/abc', 'stream-media-id' => 'video123'],
        ])]), 'acc', 'token', null, 'https://api.example.com/v4');

        $client->createDirectUpload(10, 'clip.mp4');

        self::assertSame('https://api.example.com/v4/accounts/acc/stream?direct_user=true', $this->requests[0]['url']);
    }

    /**
     * @test
     *
     * @dataProvider incompleteDirectUploadResponses
     *
     * @param array<string, string> $headers
     */
    public function it_throws_when_the_direct_upload_response_is_incomplete(array $headers): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 201, 'response_headers' => $headers])]);

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('did not return an upload location and video uid');

        $client->createDirectUpload(10, 'clip.mp4');
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function incompleteDirectUploadResponses(): iterable
    {
        yield 'no headers' => [[]];
        yield 'location only' => [['Location' => 'https://upload.cloudflarestream.com/abc']];
        yield 'uid only' => [['stream-media-id' => 'video123']];
        yield 'empty location' => [['Location' => '', 'stream-media-id' => 'video123']];
        yield 'empty uid' => [['Location' => 'https://upload.cloudflarestream.com/abc', 'stream-media-id' => '']];
    }

    /**
     * @test
     */
    public function it_reads_the_details_of_a_video(): void
    {
        $client = $this->client([new MockResponse((string) json_encode([
            'result' => ['uid' => 'video123', 'readyToStream' => true, 'status' => ['state' => 'ready', 'errorReasonText' => '']],
        ]))]);

        $details = $client->getVideo('video123');

        self::assertSame('video123', $details->uid);
        self::assertTrue($details->readyToStream);
        self::assertSame('ready', $details->state);
        self::assertNull($details->errorReason);

        self::assertSame('GET', $this->requests[0]['method']);
        self::assertSame('https://api.cloudflare.com/client/v4/accounts/acc/stream/video123', $this->requests[0]['url']);
        self::assertSame('Bearer token', $this->requests[0]['headers']['authorization']);
    }

    /**
     * @test
     */
    public function it_reports_a_processing_error_with_its_reason(): void
    {
        $client = $this->client([new MockResponse((string) json_encode([
            'result' => ['uid' => 'video123', 'readyToStream' => false, 'status' => ['state' => 'error', 'errorReasonText' => 'The file is not a video.']],
        ]))]);

        $details = $client->getVideo('video123');

        self::assertFalse($details->readyToStream);
        self::assertSame('error', $details->state);
        self::assertSame('The file is not a video.', $details->errorReason);
    }

    /**
     * @test
     */
    public function it_falls_back_to_the_requested_uid_and_an_unknown_state_when_the_details_are_sparse(): void
    {
        $client = $this->client([new MockResponse((string) json_encode(['result' => ['readyToStream' => 'yes']]))]);

        $details = $client->getVideo('video123');

        self::assertSame('video123', $details->uid);
        // Only a real boolean true counts as ready.
        self::assertFalse($details->readyToStream);
        self::assertSame('unknown', $details->state);
        self::assertNull($details->errorReason);
    }

    /**
     * @test
     */
    public function it_throws_when_the_details_have_no_result(): void
    {
        $client = $this->client([new MockResponse((string) json_encode(['success' => true]))]);

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('returned no details for video "video123"');

        $client->getVideo('video123');
    }

    /**
     * @test
     */
    public function it_throws_when_the_details_are_not_json(): void
    {
        $client = $this->client([new MockResponse('<html>')]);

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('unreadable response for video "video123"');

        $client->getVideo('video123');
    }

    /**
     * @test
     */
    public function it_throws_with_the_api_error_messages_on_a_failed_request(): void
    {
        $client = $this->client([new MockResponse((string) json_encode([
            'errors' => [['code' => 10000, 'message' => 'Authentication error'], ['message' => 'Second problem'], 'not-an-error'],
        ]), ['http_code' => 403])]);

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('Cloudflare Stream answered GET /accounts/acc/stream/video123 with HTTP 403: Authentication error; Second problem');

        $client->getVideo('video123');
    }

    /**
     * @test
     */
    public function it_throws_with_the_raw_body_when_the_error_is_not_in_the_api_format(): void
    {
        $client = $this->client([new MockResponse('Bad gateway', ['http_code' => 502])]);

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('with HTTP 502: Bad gateway');

        $client->getVideo('video123');
    }

    /**
     * @test
     */
    public function it_throws_with_a_placeholder_when_the_error_has_no_body(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 500])]);

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('with HTTP 500: no response body');

        $client->getVideo('video123');
    }

    /**
     * @test
     */
    public function it_treats_a_3xx_response_as_a_failure(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 300])]);

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('with HTTP 300');

        $client->getVideo('video123');
    }

    /**
     * @test
     */
    public function it_wraps_transport_errors(): void
    {
        $client = $this->client([new MockResponse('', ['error' => 'Could not resolve host'])]);

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('The request GET /accounts/acc/stream/video123 to Cloudflare Stream failed: Could not resolve host');

        $client->getVideo('video123');
    }

    /**
     * @test
     *
     * @dataProvider missingCredentials
     */
    public function it_refuses_to_call_cloudflare_without_credentials(string $accountId, string $apiToken): void
    {
        $client = new CloudflareStreamClient($this->httpClient([]), $accountId, $apiToken);

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('Cloudflare Stream is not configured');

        $client->getVideo('video123');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function missingCredentials(): iterable
    {
        yield 'no account id' => ['', 'token'];
        yield 'no token' => ['acc', ''];
    }

    /**
     * @test
     */
    public function it_deletes_a_video(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 200])]);

        $client->deleteVideo('video123');

        self::assertSame('DELETE', $this->requests[0]['method']);
        self::assertSame('https://api.cloudflare.com/client/v4/accounts/acc/stream/video123', $this->requests[0]['url']);
        self::assertSame('Bearer token', $this->requests[0]['headers']['authorization']);
    }

    /**
     * @test
     */
    public function it_treats_a_video_that_is_already_gone_as_deleted(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 404])]);

        $client->deleteVideo('video123');

        self::assertCount(1, $this->requests);
    }

    /**
     * @test
     */
    public function it_throws_when_a_delete_fails(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 500])]);

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('DELETE /accounts/acc/stream/video123 with HTTP 500');

        $client->deleteVideo('video123');
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function client(array $responses, ?int $maxDurationSeconds = null): CloudflareStreamClient
    {
        return new CloudflareStreamClient($this->httpClient($responses), 'acc', 'token', $maxDurationSeconds);
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function httpClient(array $responses): MockHttpClient
    {
        $queue = $responses;

        return new MockHttpClient(function (string $method, string $url, array $options) use (&$queue): MockResponse {
            $headers = [];

            /** @var list<string> $rawHeaders */
            $rawHeaders = $options['headers'] ?? [];

            foreach ($rawHeaders as $header) {
                [$name, $value] = explode(': ', $header, 2);
                $headers[strtolower($name)] = $value;
            }

            $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers];

            $response = array_shift($queue);
            self::assertInstanceOf(MockResponse::class, $response, 'More requests were made than responses queued.');

            return $response;
        });
    }
}
