<?php

declare(strict_types=1);

namespace Opengento\AdminAssistant\Api\Service;

interface CsvFormatterInterface
{
    public function formatSqlDataToCsv(array $sqlData): string;
}
