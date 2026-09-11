<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideoInterface;

/**
 * Copies Cloudflare's `readyToStream` flag onto a video. Used by the sync command as the fallback
 * for shops that cannot receive the webhook.
 */
final class ReadinessSynchronizer
{
    public function __construct(
        private readonly CloudflareStreamClientInterface $client,
    ) {
    }

    /**
     * @throws CloudflareStreamException
     */
    public function sync(CloudflareStreamProductVideoInterface $video): VideoDetails
    {
        $uid = $video->getUid();

        if (null === $uid) {
            throw new CloudflareStreamException('The video has no Cloudflare Stream uid to synchronise.');
        }

        $details = $this->client->getVideo($uid);
        $video->setReady($details->readyToStream);

        return $details;
    }
}
