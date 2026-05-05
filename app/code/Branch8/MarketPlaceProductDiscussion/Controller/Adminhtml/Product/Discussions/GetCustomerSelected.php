<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussion\Controller\Adminhtml\Product\Discussions;

use Branch8\MarketPlaceProductDiscussion\Model\Actions\GetCustomerOptions;
use Branch8\MarketPlaceProductDiscussion\Model\Actions\GetSellerOptions;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Controller for the 'catalog/product_discussions/getCustomerSellected' URL route.
 */
class GetCustomerSelected extends Action implements HttpGetActionInterface
{
    private GetCustomerOptions $getSellerOptions;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param JsonFactory $resultFactory
     * @param GetSellerOptions $getSellerOptions
     * @param Context $context
     */
    public function __construct(
        JsonFactory      $resultFactory,
        GetCustomerOptions $getSellerOptions,
        Context          $context
    )
    {
        $this->resultJsonFactory = $resultFactory;
        $this->getSellerOptions = $getSellerOptions;
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
        $options = $this->getSellerOptions->get([['field' => 'main_table.entity_id IN (?)', 'value' => $ids]])['options'];
        return $this->resultJsonFactory->create()->setData($options);
    }
}
