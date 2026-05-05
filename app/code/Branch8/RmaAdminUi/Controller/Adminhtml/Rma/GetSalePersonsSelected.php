<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Rma;

use Branch8\RmaAdminUi\Model\Actions\GetSalePersonOptions;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class AddComment
 *
 * Controller responsible for addition of the order comment to the order
 */
class GetSalePersonsSelected extends Action implements HttpGetActionInterface
{
    private GetSalePersonOptions $getSalePersonOptions;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param JsonFactory $resultFactory
     * @param GetSalePersonOptions $getSellerOptions
     * @param Context $context
     */
    public function __construct(
        JsonFactory          $resultFactory,
        GetSalePersonOptions $getSellerOptions,
        Context              $context
    )
    {
        $this->resultJsonFactory = $resultFactory;
        $this->getSalePersonOptions = $getSellerOptions;
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
        $options = $this->getSalePersonOptions->get([['field' => 'role_id IN (?)', 'value' => $ids]])['options'];
        return $this->resultJsonFactory->create()->setData($options);
    }
}
