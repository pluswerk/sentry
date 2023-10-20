<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Command;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Http\Client\HttpAsyncClient;
use Jean85\Exception\VersionMissingExceptionInterface;
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
    private HttpClientFactoryInterface $httpClientFactory;

    /** @var array<string, HttpAsyncClient> */
    private array $httpClientCache = [];

    /**
     * @throws VersionMissingExceptionInterface
     */
    public function __construct(private QueueInterface $queue)
    {
        parent::__construct('pluswerk:sentry:flush');
        $this->httpClientFactory = $this->createHttpClientFactory();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('limit-items', null, InputOption::VALUE_REQUIRED, 'How much queue entries should be processed', 60);
    }

    /**
     * @throws VersionMissingExceptionInterface
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

    protected function getClient(Entry $entry): HttpAsyncClient
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
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $sentryClient = Sentry::getInstance()->getClient();

        $i = (int)$input->getOption('limit-items');
        $output->writeln(sprintf('running with limit-items=%d', $i), $output::VERBOSITY_VERBOSE);

        do {
            $entry = $this->queue->pop();
            if (!$entry instanceof Entry) {
                break;
            }

            $i--;
            $itemIndex = $input->getOption('limit-items') - $i;
            $output->writeln(sprintf('start with entry %d', $itemIndex), $output::VERBOSITY_VERBOSE);

            $dsn = Dsn::createFromString($entry->getDsn());
            if ($entry->isEnvelope()) {
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
                $response =  $client->sendAsyncRequest($request)->wait();
                // fallback for then sendRequest is not throwing ClientErrorException
                if ($response->getStatusCode() >= 400) {
                    throw RequestException::create($request, $response);
                }
            } catch (ClientException | ClientErrorException $clientErrorException) {
                $output->writeln(sprintf('<error>could not send to sentry: %s</error>', $clientErrorException->getMessage()), $output::VERBOSITY_QUIET);
                $sentryClient && $sentryClient->captureException($clientErrorException);
            }

            $output->writeln(sprintf('done with at %d', $itemIndex), $output::VERBOSITY_VERBOSE);
        } while ($i > 0);

        $output->writeln('<info>done</info>', $output::VERBOSITY_VERBOSE);
        if ($i <= 0) {
            $output->writeln('<warning>there could be more entries</warning>', $output::VERBOSITY_VERBOSE);
        }

        return Command::SUCCESS;
    }
}
