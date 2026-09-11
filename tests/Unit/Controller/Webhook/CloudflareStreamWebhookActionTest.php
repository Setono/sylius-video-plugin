<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Controller\Webhook;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSignatureVerifier;
use Setono\SyliusVideoPlugin\Controller\Webhook\CloudflareStreamWebhookAction;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideoInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class CloudflareStreamWebhookActionTest extends TestCase
{
    use ProphecyTrait;

    private const SECRET = 'whsec_test';

    /** @var ObjectProphecy<RepositoryInterface<CloudflareStreamProductVideoInterface>> */
    private ObjectProphecy $repository;

    /** @var ObjectProphecy<ObjectManager> */
    private ObjectProphecy $manager;

    /** @var ObjectProphecy<LoggerInterface> */
    private ObjectProphecy $logger;

    protected function setUp(): void
    {
        /** @var ObjectProphecy<RepositoryInterface<CloudflareStreamProductVideoInterface>> $repository */
        $repository = $this->prophesize(RepositoryInterface::class);
        $this->repository = $repository;
        $this->manager = $this->prophesize(ObjectManager::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    /**
     * @test
     */
    public function it_marks_the_video_ready_when_cloudflare_reports_it_ready_to_stream(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');

        $this->repository->findOneBy(['uid' => 'video123'])->willReturn($video);
        $this->manager->flush()->shouldBeCalledOnce();
        $this->logger->warning(Argument::cetera())->shouldNotBeCalled();

        $response = $this->action()($this->signedRequest('{"uid":"video123","readyToStream":true,"status":{"state":"ready"}}'));

        self::assertSame(204, $response->getStatusCode());
        self::assertTrue($video->isReady());
    }

    /**
     * @test
     */
    public function it_keeps_the_video_pending_and_logs_when_cloudflare_reports_an_error(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');
        $video->setReady(true);

        $this->repository->findOneBy(['uid' => 'video123'])->willReturn($video);
        $this->manager->flush()->shouldBeCalledOnce();
        $this->logger->warning(Argument::containingString('could not process video'), Argument::withEntry('reason', 'The file is not a video.'))->shouldBeCalledOnce();

        $response = $this->action()($this->signedRequest('{"uid":"video123","readyToStream":false,"status":{"state":"error","errorReasonText":"The file is not a video."}}'));

        self::assertSame(204, $response->getStatusCode());
        self::assertFalse($video->isReady());
    }

    /**
     * @test
     */
    public function it_logs_an_unknown_reason_when_the_error_carries_none(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');

        $this->repository->findOneBy(['uid' => 'video123'])->willReturn($video);
        $this->manager->flush()->shouldBeCalledOnce();
        $this->logger->warning(Argument::type('string'), Argument::withEntry('reason', 'unknown reason'))->shouldBeCalledOnce();

        $this->action()($this->signedRequest('{"uid":"video123","status":{"state":"error"}}'));
    }

    /**
     * @test
     */
    public function it_ignores_a_notification_for_a_video_it_does_not_know(): void
    {
        $this->repository->findOneBy(['uid' => 'unknown'])->willReturn(null);
        $this->manager->flush()->shouldNotBeCalled();
        $this->logger->info(Argument::containingString('unknown video'), ['uid' => 'unknown'])->shouldBeCalledOnce();

        $response = $this->action()($this->signedRequest('{"uid":"unknown","readyToStream":true}'));

        self::assertSame(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_refuses_a_notification_with_an_invalid_signature(): void
    {
        $this->repository->findOneBy(Argument::cetera())->shouldNotBeCalled();
        $this->manager->flush()->shouldNotBeCalled();

        $request = Request::create('/webhook', 'POST', [], [], [], [], '{"uid":"video123"}');
        $request->headers->set('Webhook-Signature', 'time=' . time() . ',sig1=deadbeef');

        $response = $this->action()($request);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('Invalid webhook signature.', $response->getContent());
    }

    /**
     * @test
     */
    public function it_refuses_a_notification_without_a_signature(): void
    {
        $this->manager->flush()->shouldNotBeCalled();

        $response = $this->action()(Request::create('/webhook', 'POST', [], [], [], [], '{"uid":"video123"}'));

        self::assertSame(403, $response->getStatusCode());
    }

    /**
     * @test
     *
     * @dataProvider bodiesWithoutUid
     */
    public function it_refuses_a_signed_notification_without_a_uid(string $body): void
    {
        $this->repository->findOneBy(Argument::cetera())->shouldNotBeCalled();
        $this->manager->flush()->shouldNotBeCalled();

        $response = $this->action()($this->signedRequest($body));

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Expected a JSON body with a "uid".', $response->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function bodiesWithoutUid(): iterable
    {
        yield 'not json' => ['nope'];
        yield 'no uid' => ['{"readyToStream":true}'];
        yield 'empty uid' => ['{"uid":""}'];
        yield 'uid not a string' => ['{"uid":123}'];
    }

    private function action(): CloudflareStreamWebhookAction
    {
        return new CloudflareStreamWebhookAction(
            new WebhookSignatureVerifier(self::SECRET),
            $this->repository->reveal(),
            $this->manager->reveal(),
            $this->logger->reveal(),
        );
    }

    private function signedRequest(string $body): Request
    {
        $time = time();
        $request = Request::create('/webhook', 'POST', [], [], [], [], $body);
        $request->headers->set('Webhook-Signature', sprintf('time=%d,sig1=%s', $time, hash_hmac('sha256', $time . '.' . $body, self::SECRET)));

        return $request;
    }
}
