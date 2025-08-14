<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\EventListener;

use Pluswerk\Sentry\Service\Sentry;
use Symfony\Component\Console\Event\ConsoleErrorEvent;

final class ConsoleErrorEventListener
{
    public function __invoke(ConsoleErrorEvent $event): void
    {
        Sentry::getInstance()->withScope($event->getError());
    }
}
