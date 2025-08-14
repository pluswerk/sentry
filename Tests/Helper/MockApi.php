<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Tests\Helper;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\ExceptionMechanism;
use Throwable;
use function array_filter;
use function dd;
use function dirname;
use function file_put_contents;
use function json_decode;
use function json_encode;
use function serialize;
use function str_replace;
use const PHP_EOL;

final readonly class MockApi
{
    private string $randomSeed;
    private string $projectPath;

    public function __construct(?string $randomSeed = null, ?string $projectPath = null)
    {
        $this->projectPath = $projectPath ?? getcwd();
        $this->randomSeed = $randomSeed ?? md5(microtime(true) . random_int(0, 1000000));
    }

    public function executeScript(string $script): ScriptResult
    {
        // redirect stderr to stdout
        $command = 'SENTRY_MOCK=1 TYPO3_CONTEXT=Production SENTRY_MOCK_SEED=' . $this->randomSeed . ' ' . $script . ' 2>&1';
        $output = [];
        exec($command, $output, $exitcode);
        return new ScriptResult(
            output: implode("\n", $output),
            exitCode: $exitcode,
        );
    }

    public function client(): Client
    {
        return new Client([
            RequestOptions::HEADERS => [
                // add Seed so the file is created with the same name
                'X-Sentry-Mock-Seed' => $this->randomSeed,
            ],
            // disable Exceptions on HTTP errors
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    /**
     * @return SentryEvent[]
     */
    public function getAndEraseSentryEvents(): array
    {
        $fileName = $this->getFileName();
        if (!file_exists($fileName)) {
            return [];
        }

        $content = file_get_contents($fileName);
        if ($content === false) {
            throw new Exception('File ' . $fileName . ' could not be read. Did sentry not catch your exception?');
        }

        unlink($fileName);

        $result = [];
        $lines = explode(PHP_EOL, trim($content));
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $result[] = unserialize($line, ['allowed_classes' => SentryEvent::UNSERIALIZE_ALLOWED_CLASSES]);
        }
        return $result;
    }

    public function save(Event $eventObject): void
    {
        try {
            $user = array_filter([
                'id' => $eventObject->getUser()?->getId(),
                'username' => $eventObject->getUser()?->getEmail(),
                'email' => $eventObject->getUser()?->getUsername(),
                'segment' => $eventObject->getUser()?->getIpAddress(),
                'ipAddress' => $eventObject->getUser()?->getSegment(),
                'metadata' => $eventObject->getUser()?->getMetadata(),
            ]);


            $breadcrumbs = [];
            foreach ($eventObject->getBreadcrumbs() as $breadcrumb) {
                $breadcrumbs[] = new Breadcrumb(
                    level: $breadcrumb->getLevel(),
                    type: $breadcrumb->getType(),
                    category: $breadcrumb->getCategory(),
                    message: $breadcrumb->getMessage(),
                    metadata: json_decode(json_encode($breadcrumb->getMetadata()), true),
                    timestamp: $breadcrumb->getTimestamp(),
                );
            }
            $sentryEvent = new SentryEvent(
                id: $eventObject->getId()->__toString(),
                message: $eventObject->getMessage(),
                level: $eventObject->getLevel()?->__toString(),
                exceptions: $eventObject->getExceptions(),
                user: $user,
                tags: $eventObject->getTags(),
                extra: $eventObject->getExtra(),
                breadcrumbs: $breadcrumbs,
            );
            $fileName = $this->getFileName();
            mkdir(dirname($fileName), recursive: true);
            file_put_contents($fileName, serialize($sentryEvent) . PHP_EOL, FILE_APPEND | LOCK_EX);
            file_put_contents($fileName . '.txt', new Exception()->getTraceAsString() . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {
            dd($sentryEvent, $e);
        }
    }

    private function getFileName(): string
    {
        return $this->projectPath . '/var/sentry-mock-log/' . $this->randomSeed . '.ser';
    }
}
