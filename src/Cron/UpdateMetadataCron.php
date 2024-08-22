<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Cron;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Terminal42\ContaoDamIntegrator\Messenger\Message\UpdateMetadataMessage;

class UpdateMetadataCron
{
    public function __construct(
        private readonly string $integration,
        private readonly Connection $connection,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(): void
    {
        foreach ($this->connection->iterateAssociative('SELECT uuid FROM tl_files WHERE dam_asset_integration=?', [$this->integration]) as $file) {
            try {
                $uuid = Uuid::fromBinary($file['uuid']);
                $this->messageBus->dispatch(new UpdateMetadataMessage($uuid->toRfc4122()));
            } catch (\Throwable) {
                // Ignore if invalid
            }
        }
    }
}
