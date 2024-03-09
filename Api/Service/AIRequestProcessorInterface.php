<?php

declare(strict_types=1);

namespace Opengento\AdminAssistant\Api\Service;

interface AIRequestProcessorInterface
{
    public function execute(string $userQuestion): string;
}
