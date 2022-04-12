<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Command;

use Http\Client\Common\Exception\ClientErrorException;
use Http\Discovery\Psr17FactoryDiscovery;
use Jean85\PrettyVersions;
use Pluswerk\Sentry\Queue\Entry;
use Pluswerk\Sentry\Queue\QueueInterface;
use Pluswerk\Sentry\Service\Sentry;
use Sentry\Client;
use Sentry\Dsn;
use Sentry\HttpClient\HttpClientFactory;
use Sentry\HttpClient\HttpClientFactoryInterface;
use Sentry\Options;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FlushCommand extends Command
{
    private QueueInterface $queue;
    private HttpClientFactoryInterface $httpClientFactory;
    private array $httpClientCache = [];

    /**
     * @throws \Jean85\Exception\VersionMissingExceptionInterface
     */
    public function __construct(QueueInterface $queue)
    {
        parent::__construct('pluswerk:sentry:flush');
        $this->queue = $queue;
        $this->httpClientFactory = $this->createHttpClientFactory();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('limit-items', null, InputOption::VALUE_REQUIRED, 'How much queue entries should be processed', 60);
    }

    /**
     * @throws \Jean85\Exception\VersionMissingExceptionInterface
     */
    private function createHttpClientFactory(): HttpClientFactory
    {
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        return new HttpClientFactory(
            Psr17FactoryDiscovery::findUriFactory(),
            Psr17FactoryDiscovery::findResponseFactory(),
            $streamFactory,
            null,
            Client::SDK_IDENTIFIER,
            PrettyVersions::getVersion('sentry/sentry')->getPrettyVersion()
        );
    }

    protected function getClient(Entry $entry)
    {
        $dsn = $entry->getDsn();
        if (isset($this->httpClientCache[$dsn])) {
            return $this->httpClientCache[$dsn];
        }

        $options = new Options(['dsn' => $dsn]);
        $this->httpClientCache[$dsn] = $this->httpClientFactory->create($options);
        return $this->httpClientCache[$dsn];
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $sentryClient = Sentry::getInstance()->getClient();

        $i = (int)$input->getOption('limit-items');

        do {
            $entry = $this->queue->pop();
            if (null === $entry) {
                break;
            }

            $dsn = Dsn::createFromString($entry->getDsn());
            if ($entry->isTransaction()) {
                $request = $requestFactory->createRequest('POST', $dsn->getEnvelopeApiEndpointUrl())
                    ->withHeader('Content-Type', 'application/x-sentry-envelope')
                    ->withBody($streamFactory->createStream($entry->getPayload()));
            } else {
                $request = $requestFactory->createRequest('POST', $dsn->getStoreApiEndpointUrl())
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($streamFactory->createStream($entry->getPayload()));
            }

            $client = $this->getClient($entry);
            try {
                $client->sendAsyncRequest($request)->wait();
            } catch (ClientErrorException $clientErrorException) {
                $sentryClient && $sentryClient->captureException($clientErrorException);
            }
            $i--;
        } while ($i > 0);

        return Command::SUCCESS;
    }
}
