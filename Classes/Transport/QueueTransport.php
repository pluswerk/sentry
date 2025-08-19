<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Transport;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Pluswerk\Sentry\Queue\Entry;
use Pluswerk\Sentry\Queue\QueueInterface;
use RuntimeException;
use Sentry\Event;
use Sentry\EventType;
use Sentry\Options;
use Sentry\Response;
use Sentry\ResponseStatus;
use Sentry\Serializer\PayloadSerializerInterface;
use Sentry\Transport\Result;
use Sentry\Transport\TransportInterface;

class QueueTransport implements TransportInterface
{
    public function __construct(private Options $options, private PayloadSerializerInterface $payloadSerializer, private QueueInterface $queue)
    {
    }

    public function send(Event $event): Result
    {
        $dsn = $this->options->getDsn();

        if (!$dsn) {
            return new RejectedPromise(new RuntimeException(sprintf('The DSN option must be set to use the "%s" transport.', self::class)));
        }

        $serializedPayload = $this->payloadSerializer->serialize($event);

        $eventType = $event->getType();
        $isEnvelop = $this->options->isTracingEnabled() ||
            EventType::transaction() === $eventType ||
            EventType::checkIn() === $eventType;

        $entry = new Entry((string)$dsn, $isEnvelop, $serializedPayload);
        $this->queue->push($entry);

        $sendResponse = new Response(ResponseStatus::createFromHttpStatusCode(200));
        return new FulfilledPromise($sendResponse);
    }

    public function close(?int $timeout = null): Result
    {
        return new FulfilledPromise(true);
    }
}
