<?php
declare(strict_types=1);

namespace Branch8\SalesReports\Controller\Adminhtml\Report\Sales;

use Branch8\SalesReports\Model\Actions\GetCouponCodeOptions;
use Branch8\SalesRule\Model\Actions\GetBrandOptions;
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
class GetCouponCodeSelected extends Action implements HttpGetActionInterface
{
    private GetBrandOptions $getOptions;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param JsonFactory $resultFactory
     * @param GetBrandOptions $getCouponCodeOptions
     * @param Context $context
     */
    public function __construct(
        JsonFactory          $resultFactory,
        GetCouponCodeOptions $getCouponCodeOptions,
        Context              $context
    )
    {
        $this->resultJsonFactory = $resultFactory;
        $this->getOptions = $getCouponCodeOptions;
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
        $options = $this->getOptions->get([['field' => 'coupon_code IN (?)', 'value' => $ids]])['options'];
        return $this->resultJsonFactory->create()->setData($options);
    }
}
