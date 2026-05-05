<?php
declare(strict_types=1);


namespace Branch8\MarketPlaceParentOrderChildLink\Model\Import;

class Importer
{
    private \Magento\Catalog\Model\CategoryFactory $_categoryFactory;
    private \Magento\Framework\ObjectManagerInterface $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\CategoryFactory    $categoryFactory
    )
    {
        $this->_objectManager = $objectManager;
        $this->_categoryFactory = $categoryFactory;
    }

    /**
     * @param $filePath
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processImport($filePath)
    {
        $errorMessage = [];
        /** @var $import \Branch8\MarketPlaceParentOrderChildLink\Model\Import\Import */
        $import = $this->_objectManager->create('\Branch8\MarketPlaceParentOrderChildLink\Model\Import\Import');
        $config = [
            'entity' => 'parent_order_sub_order_link',
            'behavior' => 'append',
            'validation_strategy' => 'validation-stop-on-errors',
            'allowed_error_count' => '100',
            '_import_field_separator' => ',',
            '_import_multiple_value_separator' => ';;',
        ];
        $import->setData($config);
        $source = \Magento\ImportExport\Model\Import\Adapter::findAdapterFor(
            $import->uploadDataFileCsv($filePath),
            $this->_objectManager->create('Magento\Framework\Filesystem')
                ->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT),
            $config[$import::FIELD_FIELD_SEPARATOR]
        );
        $validationResult = $import->validateSource($source);

        if (!$validationResult) {
            $errorAggregator = $import->getErrorAggregator();
            $rowMessages = $errorAggregator->getRowsGroupedByErrorCode();
            foreach ($rowMessages as $errorCode => $rows) {
                $errorMessage[] = '****' . $errorCode . ' ' . __('in rows:') . ' ' . implode(', ', $rows);
            }
            $errorMessage[] = '******' . __(
                    'Checked rows: %1, checked entities: %2, invalid rows: %3, total errors: %4',
                    $import->getProcessedRowsCount(),
                    $import->getProcessedEntitiesCount(),
                    $errorAggregator->getInvalidRowsCount(),
                    $errorAggregator->getErrorsCount()
                );
            $this->createErrorReport($errorAggregator);
        } else {
            $import->importSource();

        }
        return $errorMessage;
    }

    protected function updateErrorReport()
    {
        $historyModel = $this->_objectManager->get('Magento\ImportExport\Model\History');
        $historyModel->loadLastInsertItem();
        if ($historyModel->getData('execution_time') != 'Validation' && $historyModel->getData('execution_time') != 'Failed') {
            $historyModel->addErrorReportFile("");
        }
    }

    /**
     * @param  $errorAggregator
     * @return string
     */
    protected function createErrorReport($errorAggregator)
    {
        /** @var \Magento\ImportExport\Model\History $historyModel */
        $historyModel = $this->_objectManager->get('Magento\ImportExport\Model\History');
        $historyModel->loadLastInsertItem();
        $sourceFile = $this->_objectManager->get('Magento\ImportExport\Helper\Report')->getReportAbsolutePath($historyModel->getImportedFile());
        $writeOnlyErrorItems = true;
        if ($historyModel->getData('execution_time') == 'Validation') {
            $writeOnlyErrorItems = false;
        }
        $fileName = $this->_objectManager->get('Magento\ImportExport\Model\Report\ReportProcessorInterface')->createReport($sourceFile, $errorAggregator, $writeOnlyErrorItems);
        if ($historyModel->getData('execution_time') == 'Validation' || $historyModel->getData('execution_time') == 'Failed') {
            $historyModel->addErrorReportFile($fileName);
        }
        return $fileName;
    }

    /** SO-18183 Check category Outlet*/
    /**
     * @return array sku
     */
    public function getProducstSkip($categoryId)
    {
        $result = array();
        $category = $this->_categoryFactory->create()->load($categoryId);
        $productCollection = $category->getProductCollection();
        if ($productCollection) {
            foreach ($productCollection as $product) {
                $result[] = $product->getSku();
            }
        }
        return $result;
    }
}
