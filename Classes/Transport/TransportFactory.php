<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Transport;

use Pluswerk\Sentry\Queue\FileQueue;
use Pluswerk\Sentry\Queue\QueueInterface;
use Sentry\Options;
use Sentry\Serializer\PayloadSerializer;
use Sentry\Transport\TransportFactoryInterface;
use Sentry\Transport\TransportInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TransportFactory implements TransportFactoryInterface
{
    public function create(Options $options): TransportInterface
    {
        $container = GeneralUtility::getContainer();
        return new QueueTransport(
            $options,
            GeneralUtility::makeInstance(PayloadSerializer::class, $options),
            GeneralUtility::makeInstance($container->has(QueueInterface::class) ? QueueInterface::class : FileQueue::class),
        );
    }
}
