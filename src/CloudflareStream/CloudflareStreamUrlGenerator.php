<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

final class CloudflareStreamUrlGenerator implements CloudflareStreamUrlGeneratorInterface
{
    private readonly string $baseUrl;

    /**
     * @param string $customerSubdomain the account's customer code as shown in the Stream dashboard,
     *                                  accepted as `abc123`, `customer-abc123` or `customer-abc123.cloudflarestream.com`
     */
    public function __construct(string $customerSubdomain)
    {
        $code = (string) preg_replace('/^customer-/', '', (string) preg_replace('/\.cloudflarestream\.com$/', '', trim($customerSubdomain)));

        $this->baseUrl = sprintf('https://customer-%s.cloudflarestream.com', $code);
    }

    public function hlsManifest(string $uid): string
    {
        return sprintf('%s/%s/manifest/video.m3u8', $this->baseUrl, $uid);
    }

    public function dashManifest(string $uid): string
    {
        return sprintf('%s/%s/manifest/video.mpd', $this->baseUrl, $uid);
    }

    public function player(string $uid): string
    {
        return sprintf('%s/%s/iframe', $this->baseUrl, $uid);
    }

    public function thumbnail(string $uid, string $time = '1s', int $height = 720): string
    {
        return sprintf('%s/%s/thumbnails/thumbnail.jpg?%s', $this->baseUrl, $uid, http_build_query(['time' => $time, 'height' => $height]));
    }
}
