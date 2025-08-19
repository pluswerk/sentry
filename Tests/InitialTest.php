<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Pluswerk\Sentry\Tests\Helper\MockApi;
use Sentry\Breadcrumb;

class InitialTest extends TestCase
{
    #[Test]
    public function httpWithoutException(): void
    {
        $mockApi = new MockApi();
        $response = $mockApi->client()->get('http://localhost:1180/');
        self::assertTrue($response->getStatusCode() === 200, 'HTTP request did not return 200');

        $body = $response->getBody()->getContents();
        self::assertStringContainsString('<h1>Content ~auto~</h1>', $body, 'Expected response body to contain "Content ~auto~"');

        $content = $mockApi->getAndEraseSentryEvents();

        self::assertEmpty($content, 'Expected 0 Sentry events to be captured');
    }

    #[Test]
    public function httpContentObjectOops(): void
    {
        $mockApi = new MockApi();
        $response = $mockApi->client()->get('http://localhost:1180/?type=500');
        self::assertTrue($response->getStatusCode() === 200, 'HTTP request did not return 200');

        $body = $response->getBody()->getContents();
        self::assertStringContainsString('<a target="_blank" href="https://sentry.example.com/organizations/sentry/issues/?project=1&query=oops_code%3A', $body, 'Expected response body to contain link to sentry instance');

        $content = $mockApi->getAndEraseSentryEvents();

        self::assertCount(1, $content, 'Expected Sentry events to be captured');

        // every exception is send 2 times, once with handled = false and once with handled = true
        foreach($content as $event) {
            $event->assertSingleException('Exception', 'Pluswerk\Sentry\Logger\SentryLogger::writeLog(): Argument #1 ($record) must be of type TYPO3\CMS\Core\Log\LogRecord, string given',);
            $event->assertExceptionFileAndLine('Classes/ContentObject/ContentObjectRenderer.php', 670);

            self::assertEquals([], $event->user, 'Expected no user data in the event');

            $event->assertTags(typo3Mode: 'frontend');
            $event->assertExtras(isCli:false);

            $categoryFUA = 'TYPO3.CMS.Frontend.Authentication.FrontendUserAuthentication';
            $categoryPEH = 'TYPO3.CMS.Frontend.ContentObject.Exception.ProductionExceptionHandler';
            self::assertEquals([
                new Breadcrumb('debug', 'default', $categoryFUA, '## Beginning of auth logging.',[],0),
                new Breadcrumb('debug', 'default', $categoryFUA, 'Login type: {type}',['type' => 'FE'],0),
                new Breadcrumb('debug', 'default', $categoryFUA, 'Login data',['status' => null,'uname' => '', 'uident' => '********','permanent' => false],0),
                new Breadcrumb('debug', 'default', $categoryFUA, 'No user session found',[],0),
                new Breadcrumb('debug', 'default', $categoryFUA, 'No usergroups found',[],0),
                new Breadcrumb('debug', 'default', $categoryFUA, 'Valid frontend usergroups: {groups}',['groups' => '0,-1'],0),
                new Breadcrumb('warning', 'default', $categoryPEH, 'Oops, an error occurred! Request: {requestId}',['exception' => [], 'code' => '', 'requestId' => []],0),
            ], $event->getBreadCrumbsWithoutTimestamp(), 'Expected FrontendUserAuthentication breadcrumbs in the event');
        }
    }

    #[Test]
    public function middlewareThrow(): void
    {
        $mockApi = new MockApi();
        $response = $mockApi->client()->get('http://localhost:1180/?throw');
        self::assertTrue($response->getStatusCode() === 500, 'HTTP request did not return 500');

        $content = $mockApi->getAndEraseSentryEvents();

        self::assertCount(1, $content, 'Expected Sentry events to be captured');

        // every exception is send 2 times, once with handled = false and once with handled = true
        foreach($content as $event) {
            $event->assertSingleException('InvalidArgumentException', 'Pluswerk\Sentry\Logger\SentryLogger::writeLog(): Argument #1 ($record) must be of type TYPO3\CMS\Core\Log\LogRecord, string given',);
            $event->assertExceptionFileAndLine('Classes/Logger/SentryLogger.php', 22);

            self::assertEquals([], $event->user, 'Expected no user data in the event');

            $event->assertTags(typo3Mode: 'frontend');
            $event->assertExtras(isCli:false);

            $categoryFUA = 'TYPO3.CMS.Frontend.Authentication.FrontendUserAuthentication';
            self::assertEquals([
                new Breadcrumb('debug', 'default', $categoryFUA, '## Beginning of auth logging.',[],0),
                new Breadcrumb('debug', 'default', $categoryFUA, 'Login type: {type}',['type' => 'FE'],0),
                new Breadcrumb('debug', 'default', $categoryFUA, 'Login data',['status' => null,'uname' => '', 'uident' => '********','permanent' => false],0),
                new Breadcrumb('debug', 'default', $categoryFUA, 'No user session found',[],0),
                new Breadcrumb('debug', 'default', $categoryFUA, 'No usergroups found',[],0),
                new Breadcrumb('debug', 'default', $categoryFUA, 'Valid frontend usergroups: {groups}',['groups' => '0,-1'],0),
            ], $event->getBreadCrumbsWithoutTimestamp(), 'Expected FrontendUserAuthentication breadcrumbs in the event');
        }

        // To assert:
        // ✅ correct error message
        // ✅ correct exception type/class
        // ✅ correct exception file and line?

        // ✅ correct tags (typo3_version, typo3_mode, php_version, application_context)
        // ✅ correct user context (username, id, email)
        // ✅ correct extras (e.g. Environment::toArray())

        // TODO find out why it is send 2 times? (only because of the handled flag?)

        // assert/Test BreadcrumbLogger
        // assert/Test SentryLogger

        // Test ContentObjectProductionExceptionHandler
        // Test ProductionExceptionHandler
        // Test DebugExceptionHandler
    }

    #[Test]
    public function cli(): void
    {
        $mockApi = new MockApi();
        $result = $mockApi->executeScript('vendor/bin/typo3 test-exception');
        self::assertFalse($result->ok(), 'CLI command did not execute successfully');

        $content = $mockApi->getAndEraseSentryEvents();
        self::assertCount(1, $content, 'Expected Sentry events to be captured');

        foreach($content as $event) {
            $event->assertSingleException('RuntimeException', 'throws a test exception');
            $event->assertExceptionFileAndLine('test_extension/Classes/Command/TestException.php', 22);

            self::assertEquals([], $event->user, 'Expected no user data in the event');

            $event->assertTags(typo3Mode: 'cli');
            $event->assertExtras(isCli: true);

            self::assertEquals([], $event->getBreadCrumbsWithoutTimestamp(), 'Expected no breadcrumbs in the event');
        }
    }
}
