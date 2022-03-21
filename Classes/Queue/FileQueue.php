<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Queue;

use Exception;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileQueue implements QueueInterface
{
    private string $directory;
    public function __construct()
    {
        $this->directory =  Environment::getVarPath() . '/tx_plussentry_queue/';
        if (!file_exists($this->directory)) {
            try {
                GeneralUtility::mkdir_deep($this->directory);
            } catch (Exception $exception) {}
        }
    }

    public function pop(): ?Entry
    {
        $file = null;
        if ($h = opendir($this->directory)) {
            while (($file = readdir($h)) !== false) {
                if ($file !== '.' && $file !== '..') {
                    break;
                }
            }
            closedir($h);
        }
        if ($file) {
            $absFile = $this->directory . $file;
            $content = @file_get_contents($absFile);
            unlink($absFile);
            if (!$content) {
                return null;
            }
            /** @noinspection JsonEncodingApiUsageInspection */
            $data = @json_decode($content, true);
            if (!$data) {
                return null;
            }
            if (!isset($data['dsn'], $data['type'], $data['payload'])) {
                return null;
            }
            return new Entry($data['dsn'], $data['type'], $data['payload']);
        }
        return null;
    }

    public function push(Entry $entry): void
    {
        /** @noinspection JsonEncodingApiUsageInspection */
        $data = @json_encode($entry);
        if (!$data) {
            return;
        }
        $fileName = $this->directory . microtime(true) . md5($data) . '.entry';
        @file_put_contents($fileName, $data);
    }
}
