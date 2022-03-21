<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Transport;

use Pluswerk\Sentry\Queue\QueueInterface;
use Sentry\Options;
use Sentry\Serializer\PayloadSerializerInterface;
use Sentry\Transport\TransportFactoryInterface;
use Sentry\Transport\TransportInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TransportFactory implements TransportFactoryInterface  {
    public function create(Options $options): TransportInterface
    {
        return new QueueTransport($options, GeneralUtility::makeInstance(PayloadSerializerInterface::class), GeneralUtility::makeInstance(QueueInterface::class));
    }
}
