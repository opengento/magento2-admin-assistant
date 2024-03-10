<?php

declare(strict_types=1);

namespace Opengento\AdminAssistant\Api\Service;

interface CsvFileNameGeneratorInterface
{
    public function generateFileName(): string;
}
