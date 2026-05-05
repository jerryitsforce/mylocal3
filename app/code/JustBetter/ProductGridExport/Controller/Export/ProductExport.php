<?php
namespace JustBetter\ProductGridExport\Controller\Export;

use JustBetter\ProductGridExport\Model\Export\Product;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Action\Action;

/**
 * Class Render
 */
class ProductExport extends Action
{
    /**
     * @var Product
     */
    protected $converter;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @param Context $context
     * @param Product $converter
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context     $context,
        Product     $converter,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->converter = $converter;
        $this->fileFactory = $fileFactory;
    }

    /**
     * Export data provider to XML
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        try {
            return $this->fileFactory->create('export.xls', $this->converter->getXlsFile(), 'var');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect($this->_redirect->getRefererUrl());
        }
    }
}
