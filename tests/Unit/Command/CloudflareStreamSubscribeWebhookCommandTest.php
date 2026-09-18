<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamException;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookClientInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSecretProviderInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSubscription;
use Setono\SyliusVideoPlugin\Command\CloudflareStreamSubscribeWebhookCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CloudflareStreamSubscribeWebhookCommandTest extends TestCase
{
    use ProphecyTrait;

    private const URL = 'https://shop.test/webhook/cloudflare_stream';

    /** @var ObjectProphecy<WebhookClientInterface> */
    private ObjectProphecy $client;

    /** @var ObjectProphecy<WebhookSecretProviderInterface> */
    private ObjectProphecy $secretProvider;

    /** @var ObjectProphecy<UrlGeneratorInterface> */
    private ObjectProphecy $urlGenerator;

    protected function setUp(): void
    {
        $this->client = $this->prophesize(WebhookClientInterface::class);
        $this->secretProvider = $this->prophesize(WebhookSecretProviderInterface::class);
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->urlGenerator->generate('_webhook_controller', ['type' => 'cloudflare_stream'], UrlGeneratorInterface::ABSOLUTE_URL)->willReturn(self::URL);
    }

    /**
     * @test
     */
    public function it_subscribes_the_applications_webhook_endpoint_when_the_account_has_no_subscription(): void
    {
        $this->client->getWebhook()->willReturn(null);
        $this->client->subscribeWebhook(self::URL)->willReturn($this->subscription(self::URL))->shouldBeCalledOnce();
        $this->secretProvider->getSecret(true)->willReturn('whsec')->shouldBeCalledOnce();

        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('now notifies', $tester->getDisplay());
        self::assertStringContainsString(self::URL, $tester->getDisplay());
        self::assertStringNotContainsString('webhook_secret', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_leaves_a_subscription_to_the_same_url_alone(): void
    {
        $this->client->getWebhook()->willReturn($this->subscription(self::URL));
        $this->client->subscribeWebhook(Argument::any())->shouldNotBeCalled();
        $this->secretProvider->getSecret(Argument::cetera())->shouldNotBeCalled();

        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('already notifies', $tester->getDisplay());
        self::assertStringContainsString(self::URL, $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_subscribes_again_when_forced(): void
    {
        $this->client->getWebhook()->willReturn($this->subscription(self::URL));
        $this->client->subscribeWebhook(self::URL)->willReturn($this->subscription(self::URL))->shouldBeCalledOnce();
        $this->secretProvider->getSecret(true)->willReturn('whsec')->shouldBeCalledOnce();

        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['--force' => true]));
        self::assertStringContainsString('now notifies', $tester->getDisplay());
        self::assertStringNotContainsString('Replacing', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_replaces_a_subscription_to_another_url_and_says_so(): void
    {
        $this->client->getWebhook()->willReturn($this->subscription('https://old.test/hook'));
        $this->client->subscribeWebhook(self::URL)->willReturn($this->subscription(self::URL))->shouldBeCalledOnce();
        $this->secretProvider->getSecret(true)->willReturn('whsec');

        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Replacing', $tester->getDisplay());
        self::assertStringContainsString('https://old.test/hook', $tester->getDisplay());
        self::assertStringContainsString('now notifies', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_subscribes_a_given_url_instead_of_the_applications(): void
    {
        $this->urlGenerator->generate(Argument::cetera())->shouldNotBeCalled();
        $this->client->getWebhook()->willReturn(null);
        $this->client->subscribeWebhook('https://elsewhere.test/hook')->willReturn($this->subscription('https://elsewhere.test/hook'))->shouldBeCalledOnce();
        $this->secretProvider->getSecret(true)->willReturn('whsec');

        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['url' => 'https://elsewhere.test/hook']));
        self::assertStringContainsString('https://elsewhere.test/hook', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_fails_when_the_webhook_endpoint_is_not_routed_and_no_url_is_given(): void
    {
        $this->urlGenerator->generate('_webhook_controller', ['type' => 'cloudflare_stream'], UrlGeneratorInterface::ABSOLUTE_URL)->willThrow(new RouteNotFoundException('Unable to generate a URL for the named route "_webhook_controller".'));
        $this->client->getWebhook()->shouldNotBeCalled();

        $tester = $this->tester();

        self::assertSame(Command::INVALID, $tester->execute([]));
        self::assertStringContainsString('not routed', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_fails_when_the_applications_public_url_is_unknown(): void
    {
        $this->urlGenerator->generate('_webhook_controller', ['type' => 'cloudflare_stream'], UrlGeneratorInterface::ABSOLUTE_URL)->willReturn('http://localhost/webhook/cloudflare_stream');
        $this->client->getWebhook()->shouldNotBeCalled();

        $tester = $this->tester();

        self::assertSame(Command::INVALID, $tester->execute([]));
        self::assertStringContainsString('default_uri', $tester->getDisplay());
        self::assertStringContainsString('http://localhost/webhook/cloudflare_stream', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_fails_when_cloudflare_cannot_be_asked_about_the_subscription(): void
    {
        $this->client->getWebhook()->willThrow(new CloudflareStreamException('Authentication error'));
        $this->client->subscribeWebhook(Argument::any())->shouldNotBeCalled();

        $tester = $this->tester();

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Authentication error', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_fails_when_the_subscription_cannot_be_created(): void
    {
        $this->client->getWebhook()->willReturn(null);
        $this->client->subscribeWebhook(self::URL)->willThrow(new CloudflareStreamException('Invalid notification URL'));
        $this->secretProvider->getSecret(Argument::cetera())->shouldNotBeCalled();

        $tester = $this->tester();

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Invalid notification URL', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_warns_when_the_configured_secret_is_not_the_one_cloudflare_returned(): void
    {
        $this->client->getWebhook()->willReturn(null);
        $this->client->subscribeWebhook(self::URL)->willReturn($this->subscription(self::URL, 'new-secret'));
        $this->secretProvider->getSecret(true)->willReturn('new-secret');

        $tester = $this->tester('old-secret');

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('webhook_secret', $tester->getDisplay());
        self::assertStringContainsString('"new-secret"', $tester->getDisplay());
    }

    /**
     * @test
     *
     * @dataProvider secretsThatNeedNoWarning
     */
    public function it_does_not_warn_when_no_other_secret_is_configured(?string $configuredSecret): void
    {
        $this->client->getWebhook()->willReturn(null);
        $this->client->subscribeWebhook(self::URL)->willReturn($this->subscription(self::URL, 'whsec'));
        $this->secretProvider->getSecret(true)->willReturn('whsec');

        $tester = $this->tester($configuredSecret);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringNotContainsString('webhook_secret', $tester->getDisplay());
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function secretsThatNeedNoWarning(): iterable
    {
        yield 'none' => [null];
        yield 'empty' => [''];
        yield 'the same' => ['whsec'];
    }

    /**
     * @test
     */
    public function it_has_a_stable_name(): void
    {
        self::assertSame('setono:sylius-video:cloudflare-stream:subscribe-webhook', CloudflareStreamSubscribeWebhookCommand::getDefaultName());
    }

    private function subscription(string $url, string $secret = 'whsec'): WebhookSubscription
    {
        return new WebhookSubscription($url, $secret);
    }

    private function tester(?string $configuredSecret = null): CommandTester
    {
        return new CommandTester(new CloudflareStreamSubscribeWebhookCommand(
            $this->client->reveal(),
            $this->secretProvider->reveal(),
            $this->urlGenerator->reveal(),
            $configuredSecret,
        ));
    }
}
