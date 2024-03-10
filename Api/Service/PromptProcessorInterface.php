<?php

declare(strict_types=1);

namespace Opengento\AdminAssistant\Api\Service;

interface PromptProcessorInterface
{
    public function getSqlDataFromPrompt(string $userQuestion): array;

    public function interpretDataDependingOnUserPrompt(string $userQuestion, array $sqlResponse): string;
}
