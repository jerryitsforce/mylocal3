<?php
namespace JustBetter\ProductGridExport\Controller\Adminhtml\Export;

use JustBetter\ProductGridExport\Model\Export\ProductInventory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;

class ProductInventoryExport extends Action
{
    /**
     * @var ProductInventory
     */
    protected $productInventory;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @param Context $context
     * @param ProductInventory $productInventory
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        ProductInventory $productInventory,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->productInventory = $productInventory;
        $this->fileFactory = $fileFactory;
    }

    /**
     * Export inventory data to CSV
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        try {
            return $this->fileFactory->create('inventory_export.xls', $this->productInventory->getXlsFile(), 'var');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('catalog/product/index');
        }
    }
}