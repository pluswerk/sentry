<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Integration;

use Sentry\Event;
use Sentry\Integration\IntegrationInterface;
use Sentry\State\Scope;

final readonly class ExtSentryIntegration implements IntegrationInterface{

    public function setupOnce(): void
    {
        dd('ExtSentryIntegration::setupOnce() called');
        Scope::addGlobalEventProcessor(static function (Event $event): Event {
            $extra = $event->getExtra();
            $extra['php_version'] ??= PHP_VERSION;
            $event->setExtra($extra);
            return $event;
        });
    }
}
