<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Tests\Helper;

use PHPUnit\Framework\Assert;
use Sentry\Breadcrumb;
use Sentry\ExceptionDataBag;
use Sentry\ExceptionMechanism;
use Sentry\Frame;
use Sentry\Stacktrace;
use Sentry\UserDataBag;
use function array_key_last;
use function assert;
use function getcwd;
use function time;
use const PHP_VERSION;

final readonly class SentryEvent
{
    public const array UNSERIALIZE_ALLOWED_CLASSES = [
        SentryEvent::class,
        ExceptionDataBag::class,
        Stacktrace::class,
        Frame::class,
        ExceptionMechanism::class,
        Breadcrumb::class,
    ];

    public function __construct(
        public string $id,
        public ?string $message = null,
        public ?string $level = null,
        /**
         * @var ExceptionDataBag[]
         */
        public array $exceptions = [],
        /**
         * @var array<string, mixed>
         */
        public ?array $user = null,
        /**
         * @var array<string, mixed>
         */
        public array $tags = [],
        /**
         * @var array<string, mixed>
         */
        public array $extra = [],
        /**
         * @var Breadcrumb[]
         */
        public array $breadcrumbs = [],
    ) {

    }

    /**
     * @return Breadcrumb[]
     */
    public function getBreadCrumbsWithoutTimestamp(): array
    {
        $breadcrumbs = [];
        foreach ($this->breadcrumbs as $breadcrumb) {
            assert($breadcrumb instanceof Breadcrumb);
            Assert::assertGreaterThan(time() - 10, $breadcrumb->getTimestamp(), 'Expected breadcrumb timestamp to be within the last 10 seconds');
            $breadcrumbs[] = $breadcrumb->withTimestamp(0); // Set timestamp to 0 for easier comparison
        }
        return $breadcrumbs;
    }

    public function getException(): ExceptionDataBag
    {
        Assert::assertCount(1, $this->exceptions, 'Expected exactly one exception in the event');
        $exception = $this->exceptions[0];
        Assert::assertInstanceOf(ExceptionDataBag::class, $exception);
        return $exception;
    }

    public function assertSingleException(string $throwableClass, string $messageContains): void
    {
        $exception = $this->getException();
        Assert::assertEquals($throwableClass, $exception->getType(), 'Exception type does not match expected value');
        Assert::assertStringContainsString($messageContains, $exception->getValue(), 'Exception message does not contain expected value');
    }

    public function getLastStackTraceFrame(): Frame
    {
        $exception = $this->getException();
        $stacktrace = $exception->getStacktrace();
        Assert::assertInstanceOf(Stacktrace::class, $stacktrace);
        $frames = $stacktrace->getFrames();
        return $frames[array_key_last($frames)];
    }

    public function assertExceptionFileAndLine(string $fileName, int $lineNumber, int $plusMinus = 5): void
    {
        $lastFrame = $this->getLastStackTraceFrame();
        Assert::assertStringEndsWith($fileName, $lastFrame->getFile(), 'Exception file does not match expected value');
        Assert::assertGreaterThanOrEqual($lineNumber - $plusMinus, $lastFrame->getLine(), 'Exception line does not match expected value');
        Assert::assertLessThanOrEqual($lineNumber + $plusMinus, $lastFrame->getLine(), 'Exception line does not match expected value');
    }

    public function assertTags(string $typo3Mode): void
    {
        Assert::assertNotEmpty($this->tags['typo3_version'], 'Expected tags "typo3_version" to not be empty');
        Assert::assertEquals($typo3Mode, $this->tags['typo3_mode'], 'Expected tags "typo3_mode" to be "frontend"');
        Assert::assertEquals(PHP_VERSION, $this->tags['php_version'], 'Expected tags "php_version" to match current PHP version');
        Assert::assertEquals('Production', $this->tags['application_context'], 'Expected tags "application_context" to be "Production"');
    }

    public function assertExtras(bool $isCli): void
    {
        $currentScript = getcwd() . '/public/index.php';
        if ($isCli) {
            $currentScript = getcwd() . '/public/typo3/sysext/core/bin/typo3';
        }
        Assert::assertEquals('Production', $this->extra['context'], 'Expected extra "context" to be "Production"');
        Assert::assertEquals($isCli, $this->extra['cli'], 'Expected extra "cli" to be ' . ($isCli ? 'true' : 'false'));
        Assert::assertEquals(getcwd(), $this->extra['projectPath'], 'Expected extra "projectPath" to match current working directory');
        Assert::assertEquals(getcwd() . '/public', $this->extra['publicPath'], 'Expected extra "publicPath" to match current working directory with /public');
        Assert::assertEquals(getcwd() . '/var', $this->extra['varPath'], 'Expected extra "varPath" to match current working directory with /var');
        Assert::assertEquals(getcwd() . '/config', $this->extra['configPath'], 'Expected extra "configPath" to match current working directory with /config');
        Assert::assertEquals($currentScript, $this->extra['currentScript'], 'Expected extra "currentScript" to match current working directory with /public/index.php',);
        Assert::assertEquals('UNIX', $this->extra['os'], 'Expected extra "os" to be "UNIX"');
    }
}
