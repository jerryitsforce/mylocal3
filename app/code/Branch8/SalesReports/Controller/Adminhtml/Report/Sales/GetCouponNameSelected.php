<?php
declare(strict_types=1);

namespace Branch8\SalesReports\Controller\Adminhtml\Report\Sales;

use Branch8\SalesReports\Model\Actions\GetCouponNameOptions;
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
class GetCouponNameSelected extends Action implements HttpGetActionInterface
{
    private GetCouponNameOptions $getCouponNameOptions;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param JsonFactory $resultFactory
     * @param GetCouponNameOptions $getCouponNameOptions
     * @param Context $context
     */
    public function __construct(
        JsonFactory      $resultFactory,
        GetCouponNameOptions $getCouponNameOptions,
        Context          $context
    )
    {
        $this->resultJsonFactory = $resultFactory;
        $this->getCouponNameOptions = $getCouponNameOptions;
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
        $options = $this->getCouponNameOptions->get([['field' => 'rule_name IN (?)', 'value' => $ids]])['options'];
        return $this->resultJsonFactory->create()->setData($options);
    }
}
