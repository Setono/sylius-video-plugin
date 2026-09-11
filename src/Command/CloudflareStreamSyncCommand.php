<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Command;

use Doctrine\Persistence\ObjectManager;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamException;
use Setono\SyliusVideoPlugin\CloudflareStream\ReadinessSynchronizer;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideoInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Asks Cloudflare Stream about every video that is not ready yet and marks the finished ones
 * ready. The webhook does this instantly; run this from cron as a fallback for shops Cloudflare
 * cannot reach, or once after setting the webhook up.
 */
#[AsCommand(
    name: 'setono:sylius-video:cloudflare-stream:sync',
    description: 'Marks Cloudflare Stream videos ready once Cloudflare has finished processing them',
)]
final class CloudflareStreamSyncCommand extends Command
{
    /**
     * @param RepositoryInterface<CloudflareStreamProductVideoInterface> $repository
     */
    public function __construct(
        private readonly ReadinessSynchronizer $synchronizer,
        private readonly RepositoryInterface $repository,
        private readonly ObjectManager $manager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ready = 0;
        $pending = 0;
        $failed = 0;

        foreach ($this->repository->findBy(['ready' => false]) as $video) {
            if (!$video instanceof CloudflareStreamProductVideoInterface || null === $video->getUid()) {
                continue;
            }

            try {
                $details = $this->synchronizer->sync($video);
            } catch (CloudflareStreamException $e) {
                ++$failed;
                $io->error(sprintf('%s: %s', $video->getUid(), $e->getMessage()));

                continue;
            }

            if ($details->readyToStream) {
                ++$ready;
                $io->text(sprintf('%s: ready', $video->getUid()));
            } else {
                ++$pending;
                $io->text(sprintf('%s: %s%s', $video->getUid(), $details->state, null === $details->errorReason ? '' : ' (' . $details->errorReason . ')'));
            }
        }

        $this->manager->flush();

        $io->success(sprintf('%d video(s) became ready, %d still processing, %d could not be checked.', $ready, $pending, $failed));

        return 0 === $failed ? Command::SUCCESS : Command::FAILURE;
    }
}
