<?php

declare(strict_types=1);

namespace Opengento\AdminAssistant\Service;

use Opengento\AdminAssistant\Api\Service\CsvFormatterInterface;
use Opengento\AdminAssistant\Model\Exception\EmptySqlDataException;
use function Assert\thatNullOr;

class CsvFormatter implements CsvFormatterInterface
{
    /**
     * @inheritDoc
     */
    public function formatSqlDataToCsv(array $sqlData): string
    {
        if (empty($sqlData)) {
            throw new EmptySqlDataException();
        }

        $headers = \array_keys($sqlData[0]);
        $result = \implode(',', $headers) . PHP_EOL;

        return \array_reduce(
            $sqlData,
            static fn ($stringifyCsv, $row) => $stringifyCsv .= implode(',', array_values($row)) . PHP_EOL,
            $result
        );
    }
}
