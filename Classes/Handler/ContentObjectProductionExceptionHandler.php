<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Handler;

use Exception;
use Pluswerk\Sentry\Service\ConfigService;
use Throwable;
use TYPO3\CMS\Frontend\ContentObject\Exception\ExceptionHandlerInterface;
use TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler;
use Pluswerk\Sentry\Service\Sentry;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;

class ContentObjectProductionExceptionHandler implements ExceptionHandlerInterface
{
    public function __construct(
        protected ProductionExceptionHandler $productionExceptionHandler,
        protected ConfigService $configService,
    ) {
    }

    /**
     * @param array<string, mixed> $contentObjectConfiguration
     * @throws Exception
     */
    public function handle(Exception $exception, ?AbstractContentObject $contentObject = null, $contentObjectConfiguration = []): string
    {
        // if parent class rethrows the exception the ProductionExceptionHandler will handle the Exception
        $result = $this->productionExceptionHandler->handle($exception, $contentObject, $contentObjectConfiguration);

        $sentry = Sentry::getInstance();
        if ($sentry->isDisabled()) {
            return $result;
        }

        $oopsCode = $this->getOopsCodeFromResult($result);
        try {
            $sentry->withScope($exception, static fn(Scope $scope): Scope => $scope->setTag('oops_code', $oopsCode));
        } catch (Throwable) {
            //ignore $sentryError
        }

        return $result . $this->getLink($oopsCode);
    }

    private function getOopsCodeFromResult(string $result): string
    {
        $explode = explode(' ', $result);
        return $explode[array_key_last($explode)];
    }

    private function getLink(string $oopsCode): string
    {
        $dsn = SentrySdk::getCurrentHub()->getClient()?->getOptions()->getDsn();
        if (!$dsn) {
            return '';
        }

        $schema = $dsn->getScheme();
        $host = $dsn->getHost();
        $organizationName = $this->configService->getOrganizationName();
        $projectId = $dsn->getProjectId();
        $url = $schema . '://' . $host . '/organizations/' . $organizationName . '/issues/?project=' . $projectId . '&query=oops_code%3A' . $oopsCode;
        return '<a target="_blank" href="' . $url . '" style="text-decoration: none !important;">&nbsp;</a>';
    }

    /**
     * @param array<array-key, mixed> $configuration
     */
    public function setConfiguration(array $configuration): void
    {
        $this->productionExceptionHandler->setConfiguration($configuration);
    }
}
