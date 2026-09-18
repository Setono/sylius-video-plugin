<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Command;

use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamException;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookClientInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSecretProviderInterface;
use Setono\SyliusVideoPlugin\Webhook\CloudflareStreamRequestParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Points Cloudflare Stream's webhook (a single one per account) at this application's webhook
 * endpoint. Idempotent: when Cloudflare already notifies the URL nothing is changed, so the command
 * can run on every deploy. The plugin reads the signing secret back from Cloudflare, so nothing has
 * to be copied into the configuration afterwards.
 */
#[AsCommand(
    name: 'setono:sylius-video:cloudflare-stream:subscribe-webhook',
    description: 'Subscribes Cloudflare Stream\'s webhook to this application\'s webhook endpoint',
)]
final class CloudflareStreamSubscribeWebhookCommand extends Command
{
    public function __construct(
        private readonly WebhookClientInterface $client,
        private readonly WebhookSecretProviderInterface $secretProvider,
        private readonly UrlGeneratorInterface $urlGenerator,
        /** The `webhook_secret` from the plugin configuration, if any */
        private readonly ?string $configuredSecret = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::OPTIONAL, 'The URL Cloudflare should notify. Defaults to this application\'s webhook endpoint for the cloudflare_stream type, with the host taken from framework.router.default_uri')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Subscribe again even when Cloudflare already notifies the URL')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $url = $this->notificationUrl($input);
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        try {
            $current = $this->client->getWebhook();

            if (null !== $current && $current->notificationUrl === $url && true !== $input->getOption('force')) {
                $io->success(sprintf('Cloudflare Stream already notifies %s.', $url));

                return Command::SUCCESS;
            }

            if (null !== $current && $current->notificationUrl !== $url) {
                $io->note(sprintf('Replacing the account\'s only webhook subscription, which notifies %s.', $current->notificationUrl));
            }

            $subscription = $this->client->subscribeWebhook($url);

            // The subscription may come with a new secret: remember it now, so the first notification verifies.
            $this->secretProvider->getSecret(true);
        } catch (CloudflareStreamException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if (null !== $this->configuredSecret && '' !== $this->configuredSecret && $this->configuredSecret !== $subscription->secret) {
            $io->warning(sprintf(
                'The configured webhook_secret is not the secret Cloudflare returned; notifications will be refused until it is updated to "%s" or unset, so that the plugin reads the secret from Cloudflare.',
                $subscription->secret,
            ));
        }

        $io->success(sprintf('Cloudflare Stream now notifies %s.', $url));

        return Command::SUCCESS;
    }

    /**
     * @throws \RuntimeException when no URL was given and the application's cannot be told
     */
    private function notificationUrl(InputInterface $input): string
    {
        $url = $input->getArgument('url');

        if (is_string($url) && '' !== $url) {
            return $url;
        }

        try {
            $url = $this->urlGenerator->generate('_webhook_controller', ['type' => CloudflareStreamRequestParser::TYPE], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (RouteNotFoundException $e) {
            throw new \RuntimeException('Symfony\'s webhook endpoint is not routed: import @FrameworkBundle/Resources/config/routing/webhook.xml, or pass the notification URL as the argument.', 0, $e);
        }

        // Outside a request the router knows no host unless framework.router.default_uri is set.
        if ('localhost' === parse_url($url, \PHP_URL_HOST)) {
            throw new \RuntimeException(sprintf('The application\'s public URL is unknown on the command line (the router generated %s): set framework.router.default_uri, or pass the notification URL as the argument.', $url));
        }

        return $url;
    }
}
