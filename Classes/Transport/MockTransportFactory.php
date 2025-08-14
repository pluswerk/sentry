<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Transport;

use Exception;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Pluswerk\Sentry\Tests\Helper\MockApi;
use Sentry\Event;
use Sentry\Options;
use Sentry\Transport\TransportFactoryInterface;
use Sentry\Transport\TransportInterface;
use TYPO3\CMS\Core\Core\Environment;
use function dd;

final class MockTransportFactory implements TransportFactoryInterface
{
    public function __construct(private string $mockSeed)
    {

    }

    public function create(Options $options): TransportInterface
    {
        $mockApi = new MockApi($this->mockSeed, Environment::getProjectPath());
        return new class($mockApi) implements TransportInterface {
            public function __construct(
                private MockApi $mockApi,
            ) {}

            public function send(Event $event): PromiseInterface
            {
                $this->mockApi->save($event);

                return new Promise(fn() => $event->getId(), null);
            }

            public function close(?int $timeout = null): PromiseInterface
            {
                return new Promise(null, null);
            }
        };
    }
}
