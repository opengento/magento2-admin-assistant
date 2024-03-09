<?php

namespace Opengento\AdminAssistant\Service;

use JsonException;
use Magento\Framework\Exception\LocalizedException;
use Opengento\OpenAIConnector\Api\GPTCompletionsInterface;
use Opengento\AdminAssistant\Api\Service\PromptProcessorInterface;
use Magento\Framework\App\ResourceConnection;
use Opengento\OpenAIConnector\Model\Exception\OpenAICompletionException;
use const JSON_THROW_ON_ERROR;

class PromptProcessor implements PromptProcessorInterface
{
    public const MAX_CALL = 3;

    public function __construct(
        protected GPTCompletionsInterface $GPTCompletions,
        protected ResourceConnection      $resourceConnection,
    ) {
    }

    /**
     * @throws OpenAICompletionException
     * @throws JsonException
     * @throws LocalizedException
     */
    public function execute(string $userQuestion): string
    {
        $sqlRequest = $this->generateInitialRequest($userQuestion);

        $sqlResponse = null;
        $try         = 0;
        while ($try < static::MAX_CALL) {
            try {
                $sqlResponse = $this->resourceConnection->getConnection()->fetchAll($sqlRequest);
                break;
            } catch (\Exception $e) {
                $sqlRequest = $this->fixRequest($userQuestion, $e->getMessage());
                $try++;
            }
        }

        return $sqlResponse
            ? $this->interpretDataDependingOnUserPrompt($userQuestion, $sqlResponse)
            : throw new LocalizedException(__('ChatGPT is unable to handle the question'));
    }

    /**
     * @throws OpenAICompletionException
     */
    protected function generateInitialRequest(string $userQuestion): string
    {
        $response = $this->GPTCompletions->getGPTCompletions(
            prompt     : __(
                             "I ask myself this question: %1. Just write me the SQL query, don't add explanations!",
                             $userQuestion
                         )->render(),
            temperature: 0
        );

        return $this->extractSqlStatementFromString($response);
    }

    /**
     * @throws OpenAICompletionException
     */
    protected function fixRequest(string $userQuestion, string $errorMessage): string
    {
        $response = $this->GPTCompletions->getGPTCompletions(
            prompt     : __(
                             "I ask myself this question: %1. Base yourself on this error: %2. Just write me the SQL query, don't add explanations!",
                             $userQuestion,
                             $errorMessage
                         )->render(),
            temperature: 0
        );

        return $this->extractSqlStatementFromString($response);
    }

    /**
     * @throws OpenAICompletionException
     * @throws JsonException
     */
    protected function interpretDataDependingOnUserPrompt(string $userQuestion, array $sqlResponse): string
    {
        return $this->GPTCompletions->getGPTCompletions(
            prompt     : __(
                             "I ask myself this question: %1. I have this data as a result: %2. Answer the question concisely by interpreting the data. Forget the code, you're talking to a salesperson, a marketing manager or a communications manager.",
                             $userQuestion,
                             json_encode($sqlResponse, JSON_THROW_ON_ERROR)
                         )->render(),
            temperature: 0
        );
    }

    protected function extractSqlStatementFromString(string $text): string
    {
        if (preg_match('/```sql(.*?)```/s', $text, $matches)) {
            return trim($matches[1]);
        }

        return $text;
    }
}
