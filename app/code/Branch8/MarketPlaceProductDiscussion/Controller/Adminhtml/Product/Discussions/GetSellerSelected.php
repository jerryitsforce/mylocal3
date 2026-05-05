<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussion\Controller\Adminhtml\Product\Discussions;

use Branch8\MarketPlaceProductDiscussion\Model\Actions\GetSellerOptions;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Controller for the 'catalog/product_discussions/getsellerselected' URL route.
 */
class GetSellerSelected extends Action implements HttpGetActionInterface
{
    private GetSellerOptions $getSellerOptions;

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
        GetSellerOptions $getSellerOptions,
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
        $options = $this->getSellerOptions->get([['field' => 'main_table.seller_id IN (?)', 'value' => $ids]])['options'];
        return $this->resultJsonFactory->create()->setData($options);
    }
}
