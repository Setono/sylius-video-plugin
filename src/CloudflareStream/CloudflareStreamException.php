<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

/**
 * A Cloudflare Stream API call failed: a transport error, a non-2xx response or a response
 * missing what the plugin needs (e.g. the upload location).
 */
final class CloudflareStreamException extends \RuntimeException
{
}
