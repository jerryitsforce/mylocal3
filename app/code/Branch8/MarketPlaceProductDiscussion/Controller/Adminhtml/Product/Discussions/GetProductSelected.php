<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussion\Controller\Adminhtml\Product\Discussions;

use Branch8\MarketPlaceProductDiscussion\Model\Actions\GetProductOptions;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Controller for the 'catalog/product_discussions/getproductselected' URL route.
 */
class GetProductSelected extends Action implements HttpGetActionInterface
{
    private GetProductOptions $getProductOptions;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param JsonFactory $resultFactory
     * @param GetProductOptions $getProductOptions
     * @param Context $context
     */
    public function __construct(
        JsonFactory       $resultFactory,
        GetProductOptions $getProductOptions,
        Context           $context
    )
    {
        $this->resultJsonFactory = $resultFactory;
        $this->getProductOptions = $getProductOptions;
        parent::__construct($context);
    }

    /**
     * Return selected asset options.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $ids = $this->getRequest()->getParam('ids');
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $options = $this->getProductOptions->get([['field' => 'e.entity_id IN (?)', 'value' => $ids]])['options'];
        return $this->resultJsonFactory->create()->setData($options);
    }
}
