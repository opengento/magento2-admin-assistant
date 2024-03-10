<?php

declare(strict_types=1);

namespace Opengento\AdminAssistant\Service;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Opengento\AdminAssistant\Api\Service\CsvFileNameGeneratorInterface;

class CsvFileNameGenerator implements CsvFileNameGeneratorInterface
{
    public function __construct(
        protected TimezoneInterface $timezone
    ) {
    }

    public function generateFileName(): string
    {
        $currentDateTime = $this->timezone->date();
        $year = $currentDateTime->format('Y');
        $month = $currentDateTime->format('m');
        $day = $currentDateTime->format('d');
        $hour = $currentDateTime->format('H');
        $minute = $currentDateTime->format('i');

        return sprintf('admin_assistant_%s_%s_%s_%s_%s.csv', $year, $month, $day, $hour, $minute);
    }
}
