<?php

declare(strict_types=1);

namespace Opengento\AdminAssistant\Api\Service;

interface PromptProcessorInterface
{
    public function execute(string $userQuestion): string;
}
