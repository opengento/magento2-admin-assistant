<?php

declare(strict_types=1);

namespace Opengento\AdminAssistant\Api\Service;

use Opengento\AdminAssistant\Model\Exception\EmptySqlDataException;

interface CsvFormatterInterface
{
    /**
     * @throws EmptySqlDataException
     */
    public function formatSqlDataToCsv(array $sqlData): string;
}
