<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Thin client for the parts of the Cloudflare Stream API the plugin uses, authenticated with an
 * API token that has the "Stream: Edit" permission.
 */
final class CloudflareStreamClient implements CloudflareStreamClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $accountId,
        private readonly string $apiToken,
        private readonly ?int $maxDurationSeconds = null,
        private readonly string $apiBaseUrl = 'https://api.cloudflare.com/client/v4',
    ) {
    }

    public function createDirectUpload(int $size, string $name): DirectUpload
    {
        // A tus creation request with `direct_user=true` returns a one-time upload URL in the
        // Location header and the uid of the video-to-be in `stream-media-id`.
        $metadata = ['name ' . base64_encode($name)];

        if (null !== $this->maxDurationSeconds) {
            $metadata[] = 'maxDurationSeconds ' . base64_encode((string) $this->maxDurationSeconds);
        }

        $response = $this->request('POST', sprintf('/accounts/%s/stream?direct_user=true', $this->accountId), [
            'headers' => [
                'Tus-Resumable' => '1.0.0',
                'Upload-Length' => (string) $size,
                'Upload-Metadata' => implode(',', $metadata),
            ],
        ]);

        $headers = $this->headers($response);
        $uploadUrl = $headers['location'][0] ?? null;
        $uid = $headers['stream-media-id'][0] ?? null;

        if (null === $uploadUrl || '' === $uploadUrl || null === $uid || '' === $uid) {
            throw new CloudflareStreamException('Cloudflare Stream did not return an upload location and video uid for the direct upload.');
        }

        return new DirectUpload($uploadUrl, $uid);
    }

    public function getVideo(string $uid): VideoDetails
    {
        $response = $this->request('GET', sprintf('/accounts/%s/stream/%s', $this->accountId, $uid));

        try {
            $payload = $response->toArray(false);
        } catch (ExceptionInterface $e) {
            throw new CloudflareStreamException(sprintf('Cloudflare Stream returned an unreadable response for video "%s": %s', $uid, $e->getMessage()), 0, $e);
        }

        $result = $payload['result'] ?? null;

        if (!is_array($result)) {
            throw new CloudflareStreamException(sprintf('Cloudflare Stream returned no details for video "%s".', $uid));
        }

        $status = is_array($result['status'] ?? null) ? $result['status'] : [];
        $errorReason = $status['errorReasonText'] ?? null;

        return new VideoDetails(
            is_string($result['uid'] ?? null) ? $result['uid'] : $uid,
            true === ($result['readyToStream'] ?? false),
            is_string($status['state'] ?? null) ? $status['state'] : 'unknown',
            is_string($errorReason) && '' !== $errorReason ? $errorReason : null,
        );
    }

    public function deleteVideo(string $uid): void
    {
        $this->request('DELETE', sprintf('/accounts/%s/stream/%s', $this->accountId, $uid), [], [404]);
    }

    /**
     * @param array<string, mixed> $options
     * @param list<int> $acceptedErrorStatuses response codes outside 2xx that are not failures
     */
    private function request(string $method, string $path, array $options = [], array $acceptedErrorStatuses = []): ResponseInterface
    {
        $headers = is_array($options['headers'] ?? null) ? $options['headers'] : [];
        $options['headers'] = $headers + ['Authorization' => 'Bearer ' . $this->apiToken];

        try {
            $response = $this->httpClient->request($method, $this->apiBaseUrl . $path, $options);
            $status = $response->getStatusCode();
        } catch (ExceptionInterface $e) {
            throw new CloudflareStreamException(sprintf('The request %s %s to Cloudflare Stream failed: %s', $method, $path, $e->getMessage()), 0, $e);
        }

        if (($status < 200 || $status >= 300) && !in_array($status, $acceptedErrorStatuses, true)) {
            throw new CloudflareStreamException(sprintf('Cloudflare Stream answered %s %s with HTTP %d: %s', $method, $path, $status, $this->errorMessage($response)));
        }

        return $response;
    }

    /**
     * @return array<array-key, array<string>>
     */
    private function headers(ResponseInterface $response): array
    {
        try {
            return $response->getHeaders(false);
        } catch (ExceptionInterface $e) {
            throw new CloudflareStreamException(sprintf('Could not read the Cloudflare Stream response headers: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * The API reports errors as `{"errors": [{"code": 10005, "message": "..."}]}`; fall back to the raw body.
     */
    private function errorMessage(ResponseInterface $response): string
    {
        try {
            $body = $response->getContent(false);
        } catch (ExceptionInterface) {
            return 'no response body';
        }

        $payload = json_decode($body, true);
        $errors = is_array($payload) && is_array($payload['errors'] ?? null) ? $payload['errors'] : [];
        $messages = [];

        foreach ($errors as $error) {
            if (is_array($error) && is_string($error['message'] ?? null)) {
                $messages[] = $error['message'];
            }
        }

        return [] !== $messages ? implode('; ', $messages) : ('' === $body ? 'no response body' : $body);
    }
}
