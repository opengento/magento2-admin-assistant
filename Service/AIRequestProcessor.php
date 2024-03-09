<?php

namespace Opengento\AdminAssistant\Service;

use JsonException;
use Magento\Framework\Exception\LocalizedException;
use Opengento\OpenAIConnector\Api\GPTCompletionsInterface;
use Opengento\AdminAssistant\Api\Service\AIRequestProcessorInterface;
use Magento\Framework\App\ResourceConnection;
use Opengento\OpenAIConnector\Model\Exception\OpenAICompletionException;
use const JSON_THROW_ON_ERROR;

class AIRequestProcessor implements AIRequestProcessorInterface
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
        return $this->GPTCompletions->getGPTCompletions(
            prompt     : __(
                             "Je me pose cette question : %1. Ecrit moi uniquement la requete SQL, n'ajoute pas d'explications !",
                             $userQuestion
                         )->render(),
            temperature: 0
        );
    }

    /**
     * @throws OpenAICompletionException
     */
    protected function fixRequest(string $userQuestion, string $errorMessage): string
    {
        return $this->GPTCompletions->getGPTCompletions(
            prompt     : __(
                             "Je me pose cette question : %1. Base toi sur cette erreur : %2. Ecrit moi uniquement la requete SQL, n'ajoute pas d'explications !",
                             $userQuestion,
                             $errorMessage
                         )->render(),
            temperature: 0
        );
    }

    /**
     * @throws OpenAICompletionException
     * @throws JsonException
     */
    protected function interpretDataDependingOnUserPrompt(string $userQuestion, array $sqlResponse): string
    {
        return $this->GPTCompletions->getGPTCompletions(
            prompt     : __(
                             "Je me pose cette question : %1. J'ai cette donnée comme résultat : %2. Réponds à la question de manière concis en intérpretant les données. Oublie le coté code, tu t'adresse à un commercial, un responsable de marketing ou à un responsable de communication.",
                             $userQuestion,
                             json_encode($sqlResponse, JSON_THROW_ON_ERROR)
                         )->render(),
            temperature: 0
        );
    }
}
