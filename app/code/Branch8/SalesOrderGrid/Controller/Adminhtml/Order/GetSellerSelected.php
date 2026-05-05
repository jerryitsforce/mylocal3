<?php
declare(strict_types=1);

namespace Branch8\SalesOrderGrid\Controller\Adminhtml\Order;

use Branch8\SalesOrderGrid\Model\Actions\GetSellerOptions;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Cms\Helper\Wysiwyg\Images;
use Magento\Cms\Model\Wysiwyg\Images\Storage;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\MediaGalleryApi\Api\GetAssetsByIdsInterface;

/**
 * Class AddComment
 *
 * Controller responsible for addition of the order comment to the order
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
            return $this->resultJsonFactory->create()->setData('parameter ids must be type of array');
        }
        $options = $this->getSellerOptions->get([['field' => 'main_table.seller_id IN (?)', 'value' => $ids]])['options'];
        return $this->resultJsonFactory->create()->setData($options);
    }
}
