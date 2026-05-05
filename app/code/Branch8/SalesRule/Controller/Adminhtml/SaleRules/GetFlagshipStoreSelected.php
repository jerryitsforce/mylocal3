<?php
declare(strict_types=1);

namespace Branch8\SalesRule\Controller\Adminhtml\SaleRules;

use Branch8\SalesRule\Model\Actions\GetFlagshipStoreOptions;
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
class GetFlagshipStoreSelected extends Action implements HttpGetActionInterface
{
    private GetFlagshipStoreOptions $getFlagshipStoreOptions;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param JsonFactory $resultFactory
     * @param GetFlagshipStoreOptions $getFlagshipStoreOptions
     * @param Context $context
     */
    public function __construct(
        JsonFactory      $resultFactory,
        GetFlagshipStoreOptions $getFlagshipStoreOptions,
        Context          $context
    )
    {
        $this->resultJsonFactory = $resultFactory;
        $this->getFlagshipStoreOptions = $getFlagshipStoreOptions;
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
        $options = $this->getFlagshipStoreOptions->get([['field' => 'main_table.entity_id IN (?)', 'value' => $ids]])['options'];
        return $this->resultJsonFactory->create()->setData($options);
    }
}
