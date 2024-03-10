<?php

declare(strict_types=1);

namespace Opengento\AdminAssistant\Controller\Adminhtml\Prompt;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
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
        protected CsvFormatter $csvFormatter
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $prompt = $this->getRequest()->getParam('prompt');
        try  {
            $sqlData = $this->promptProcessor->getSqlDataFromPrompt($prompt);

            if (\count($sqlData) > 10) {
                return $this->fileFactory->create(
                    $this->csvFileNameGenerator->generateFileName(),
                    $this->csvFormatter->formatSqlDataToCsv($sqlData),
                    DirectoryList::VAR_DIR,
                    'text/csv'
                );
            }

            return $this->promptProcessor->interpretDataDependingOnUserPrompt($prompt, $sqlData);
        } catch (\Exception) {
            // TODO: Afficher message erreur
        }
    }
}
