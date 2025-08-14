<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Command;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Http\Client\Common\Exception\ClientErrorException;
use Http\Client\HttpAsyncClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Jean85\Exception\VersionMissingExceptionInterface;
use Jean85\PrettyVersions;
use Pluswerk\Sentry\Queue\Entry;
use Pluswerk\Sentry\Queue\QueueInterface;
use Pluswerk\Sentry\Service\Sentry;
use Psr\Http\Message\ResponseInterface;
use Sentry\Client;
use Sentry\Dsn;
use Sentry\HttpClient\HttpClientFactory;
use Sentry\HttpClient\HttpClientFactoryInterface;
use Sentry\Options;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;
use function sprintf;
use function usleep;

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
        $this->addOption('req-per-sec', null, InputOption::VALUE_REQUIRED, 'How many requests per second should be sent', 5);
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

        $option = $input->getOption('limit-items');
        assert(is_string($option) || is_int($option));
        $reqPerSec = $input->getOption('req-per-sec');
        assert(is_string($reqPerSec) || is_int($reqPerSec));
        $reqPerSec = (int)$reqPerSec;
        $i = (int)$option;
        $option = (int)$option;
        $output->writeln(sprintf('running with limit-items=%d', $i), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(sprintf('to do: %d queued entries', $this->queue->count() ?? -1), OutputInterface::VERBOSITY_VERBOSE);

        $lastTime = microtime(true);
        do {
            $entry = $this->queue->pop();
            if (!$entry instanceof Entry) {
                break;
            }

            $i--;
            $itemIndex = $option - $i;
            $output->writeln(sprintf('start with entry %d', $itemIndex), OutputInterface::VERBOSITY_VERBOSE);

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
                if ($response instanceof ResponseInterface && $response->getStatusCode() >= 400) {
                    throw RequestException::create($request, $response);
                }
            } catch (ClientException | ClientErrorException $clientErrorException) {
                $output->writeln(sprintf('<error>could not send to sentry: %s</error>', $clientErrorException->getMessage()), OutputInterface::VERBOSITY_QUIET);
                $sentryClient && $sentryClient->captureException($clientErrorException);
                if ($clientErrorException->getResponse()->getStatusCode() === 429) {
                    $output->writeln('<error>Rate limit reached, waiting for sentry to recover sleep(5s)</error>', OutputInterface::VERBOSITY_QUIET);
                    sleep(5); // wait for sentry to recover
                }
            }

            $output->writeln(sprintf('done with at %d', $itemIndex), OutputInterface::VERBOSITY_VERBOSE);
            if ($i % $reqPerSec === 0) {
                $toSleep = max(0, (int)(1_000_000 - (microtime(true) - $lastTime) * 1_000_000));
                if ($toSleep) {
                    $output->writeln(sprintf('%d req/s (sleep %dms)', $reqPerSec, $toSleep / 1_000), OutputInterface::VERBOSITY_VERBOSE);
                    usleep($toSleep);
                }

                $lastTime = microtime(true);
            }
        } while ($i > 0);

        $output->writeln('<info>done</info>', OutputInterface::VERBOSITY_VERBOSE);
        if ($i <= 0) {
            $output->writeln('<warning>there could be more entries</warning>', OutputInterface::VERBOSITY_VERBOSE);
        }

        return Command::SUCCESS;
    }
}
