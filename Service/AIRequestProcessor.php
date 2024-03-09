<?php

namespace Opengento\AdminAssistant\Service;

use Opengento\OpenAIConnector\Api\GPTCompletionsInterface;
use Opengento\AdminAssistant\Api\Service\AIRequestProcessorInterface;
use Magento\Framework\App\ResourceConnection;
use Opengento\OpenAIConnector\Model\Exception\OpenAICompletionException;

class AIRequestProcessor implements AIRequestProcessorInterface
{
    public const MAX_CALL = 3;

    public function __construct(
        protected GPTCompletionsInterface $GPTCompletions,
        protected ResourceConnection      $resourceConnection,
    ) {
    }

    public function execute(string $userQuestion): string
    {
        try {
            $request = $this->generateInitialRequest($userQuestion);
        } catch (OpenAICompletionException $e) {
            return 'Error' . $e->getMessage();
        }

        $requestResponse = null;
        $try             = 0;
        while ($try < static::MAX_CALL) {
            try {
                $requestResponse = $this->resourceConnection->getConnection()->fetchAll($request);
                break;
            } catch (\Exception $e) {
                $request = $this->fixRequest($userQuestion, $e->getMessage());
                $try++;
            }
        }

        try {
            return $this->interpretDataDependingOnUserPrompt($userQuestion, $requestResponse);
        } catch (OpenAICompletionException $e) {
            return 'Error' . $e->getMessage();
        }
    }

    /**
     * @throws OpenAICompletionException
     */
    protected function generateInitialRequest(string $userQuestion): string
    {
        return $this->GPTCompletions->getGPTCompletions(
            prompt     : "
Je me pose cette question : $userQuestion.
Ecrit moi uniquement la requete SQL, n'ajoute pas d'explications !
",
            temperature: 0
        );
    }

    /**
     * @throws OpenAICompletionException
     */
    protected function fixRequest(string $userQuestion, string $errorMessage): string
    {
        return $this->GPTCompletions->getGPTCompletions(
            prompt     : "
Je me pose cette question : $userQuestion.
Base toi sur cette erreur : $errorMessage.
Ecrit moi uniquement la requete SQL, n'ajoute pas d'explications !
",
            temperature: 0
        );
    }

    /**
     * @throws OpenAICompletionException
     */
    protected function interpretDataDependingOnUserPrompt(string $userQuestion, array $requestData): string
    {
        return $this->GPTCompletions->getGPTCompletions(
            prompt     : "
Je me pose cette question : $userQuestion.
J'ai cette donnée comme résultat :" . json_encode($requestData) . ".
Réponds à la question de manière concis en intérpretant les données. Oublie le coté code, tu t'adresse à un commercial, un responsable de marketing ou à un responsable de communication.
",
            temperature: 0
        );
    }
}
