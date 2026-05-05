<?php
namespace Branch8\ProductPoint\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory as ResultRawFactory;
use Branch8\Catalog\Helper\Data as CatalogHelper;

class Message extends Action
{
    /**
     * @var ResultRawFactory
     */
    private ResultRawFactory $_resultRawFactory;

    /**
     * @var CatalogHelper
     */
    private CatalogHelper $catalogHelper;

    /**
     * Widget constructor.
     * @param Context $context
     * @param CatalogHelper $catalogHelper
     * @param ResultRawFactory $resultRawFactory
     */
    public function __construct(
        Context $context,
        CatalogHelper $catalogHelper,
        ResultRawFactory $resultRawFactory
    ){
        parent::__construct($context);
        $this->_resultRawFactory = $resultRawFactory;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * @return Raw
     */
    public function execute()
    {
        $result = $this->_resultRawFactory->create();
        $result->setHeader('Content-Type', 'text/html');
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        if ($this->getRequest()->isAjax()) {
            $price = (int) $this->getRequest()->getParam('price');
            $pointType = $this->getRequest()->getParam('point_type');
            $pointProductPoint = $this->getRequest()->getParam('point_product_point');
            $pointLowerType = $this->getRequest()->getParam('point_lower_type');
            $pointLowerValue = (int) $this->getRequest()->getParam('point_lower_value');
            $pointUpperType = $this->getRequest()->getParam('point_upper_type');
            $pointUpperValue = (int) $this->getRequest()->getParam('point_upper_value');
            $content = $this->catalogHelper->getPointOfProduct($price, $pointType, $pointProductPoint, $pointLowerType, $pointLowerValue, $pointUpperType, $pointUpperValue);
            $result->setContents($content);
        }

        return $result;
    }
}
