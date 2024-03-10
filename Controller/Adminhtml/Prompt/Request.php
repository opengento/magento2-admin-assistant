<?php

declare(strict_types=1);

namespace Opengento\AdminAssistant\Controller\Adminhtml\Prompt;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Opengento\AdminAssistant\Api\Service\CsvFileNameGeneratorInterface;
use Opengento\AdminAssistant\Api\Service\PromptProcessorInterface;
use Opengento\AdminAssistant\Service\CsvFormatter;

class Request extends Action
{
    public function __construct(
        Context                            $context,
        protected PromptProcessorInterface $promptProcessor,
        protected FileFactory $fileFactory,
        protected CsvFileNameGeneratorInterface $csvFileNameGenerator,
        protected CsvFormatter $csvFormatter,
        protected JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $prompt = $this->getRequest()->getParam('prompt');
        try  {
            $resultJson = $this->jsonFactory->create();
            $sqlData = $this->promptProcessor->getSqlDataFromPrompt($prompt);

            if (\count($sqlData) > 2) {
                $response =  [
                    'fileName' => $this->csvFileNameGenerator->generateFileName(),
                    'fileContent' => $this->csvFormatter->formatSqlDataToCsv($sqlData),
                ];

                return $resultJson->setData($response);
            }

            $response = [
                'answer' => $this->promptProcessor->interpretDataDependingOnUserPrompt($prompt, $sqlData)
            ];
            return $resultJson->setData($response);
        } catch (\Exception $e) {
            $response = [
                'error' => $e->getMessage()
            ];
            return $resultJson->setData($response);
        }
    }
}
