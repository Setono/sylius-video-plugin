<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Controller\Admin;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClientInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Called by the admin product form's upload controller before it sends a file to Cloudflare:
 * creates the direct creator upload and hands back the one-time upload URL and the video uid.
 * Mounted under the admin prefix, so the admin firewall guards it.
 */
final class CloudflareStreamDirectUploadAction
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly CloudflareStreamClientInterface $client,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(Request $request): JsonResponse
    {
        // The custom header doubles as a CSRF guard: a cross-site form post cannot set it, and a
        // cross-origin script would need a CORS preflight the admin does not answer.
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'This endpoint only accepts XMLHttpRequest calls.'], Response::HTTP_BAD_REQUEST);
        }

        $payload = json_decode($request->getContent(), true);
        $name = is_array($payload) ? ($payload['name'] ?? null) : null;
        $size = is_array($payload) ? ($payload['size'] ?? null) : null;

        if (!is_string($name) || '' === trim($name) || !is_int($size) || $size <= 0) {
            return new JsonResponse(['error' => 'Expected a JSON body with a non-empty "name" and a positive integer "size".'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $upload = $this->client->createDirectUpload($size, trim($name));
        } catch (CloudflareStreamException $e) {
            $this->logger->error('Could not create a Cloudflare Stream direct upload for "{name}": {message}', [
                'name' => $name,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        return new JsonResponse(['uploadUrl' => $upload->uploadUrl, 'uid' => $upload->uid]);
    }
}
