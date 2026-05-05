<?php

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Rma;

use Magento\Cms\Model\Page\DomValidationState;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\Dom\ValidationException;
use Magento\Framework\Config\Dom\ValidationSchemaException;
use Magento\Cms\Model\Page\CustomLayout\CustomLayoutValidator;
use Magento\Framework\Filter\FilterInput;

/**
 * Controller helper for user input.
 */
class ValidateHandler
{
    /**
     * @var array
     */
    private $errorMessages = [];
    /**
     * @var \Magento\Framework\View\Model\Layout\Update\ValidatorFactory
     */
    protected $validatorFactory;

    /**
     * @var DomValidationState
     */
    private $validationState;

    /**
     * @var CustomLayoutValidator
     */
    private $customLayoutValidator;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_conn;
    /** @var \Branch8\CTBC\Model\OrderManagement $orderManagement */
    protected $orderManagement;

    /**
     * @param \Magento\Framework\View\Model\Layout\Update\ValidatorFactory $validatorFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param DomValidationState|null $validationState
     * @param CustomLayoutValidator|null $customLayoutValidator
     */
    public function __construct(
        \Magento\Framework\View\Model\Layout\Update\ValidatorFactory $validatorFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Branch8\CTBC\Model\OrderManagement $orderManagement,
        DomValidationState                                           $validationState = null,
        CustomLayoutValidator                                        $customLayoutValidator = null
    )
    {
        $this->validatorFactory = $validatorFactory;
        $this->orderManagement = $orderManagement;
        $this->validationState = $validationState
            ?: ObjectManager::getInstance()->get(DomValidationState::class);
        $this->customLayoutValidator = $customLayoutValidator
            ?: ObjectManager::getInstance()->get(CustomLayoutValidator::class);
            $this->_conn = $resourceConnection->getConnection();
    }

    /**
     * @param $data
     * @return array
     */
    public function validate($data)
    {
        $this->validateRequireEntry($data);
        $this->validateRmaItems($data);
        $this->validateEcpayInvoiceTag($data);
        return [count($this->errorMessages) > 0, $this->errorMessages];
    }

    /**
     * @param $data
     * @return void
     */
    private function validateRmaItems($data)
    {
        if (empty($data['list_rma_items'])) {
            $this->errorMessages[] = __('Empty RMA Items.');
            return;
        }
        $items = array_filter($data['list_rma_items'], function ($item) {
            if (filter_var($item['is_checked'], FILTER_VALIDATE_BOOL)) {
                return $item;
            };
        });
        if (empty($items)) {
            $this->errorMessages[] = __('Empty RMA Items.');
        }
    }

    /**
     * @param array $data
     * @return void
     */
    private function validateRequireEntry(array $data)
    {
        $requiredFields = [
            'order_id' => __('Order ID'),
            'resolution_type' => __('Resolution type'),
            'order_status' => __('Order status'),
            'reasons' => __('Reasons'),
            'description' => __('Description'),
            'rma_receiver' => __('Rma Receiver'),
           // 'rma_phone' => __('Rma Phone'),
            'rma_address' => __('Rma Address'),
            'rma_city' => __('Rma City'),
            //'rma_zipcode' => __('Rma Zip Code'),
            'rma_district' => __('Rma District'),
            'rma_delivery_time' => __('Rma Delivery Time'),
        ];
        foreach ($data as $field => $value) {
            if (in_array($field, array_keys($requiredFields)) && $value == '') {
                $this->errorMessages[] = (
                __('To apply changes you should fill in hidden required "%1" field', $requiredFields[$field])
                );
            }
        }
    }

    private function validateEcpayInvoiceTag($rmaData){
        $subOrderId = $rmaData['order_id'];
        $sqlOrder = $this->_conn->select()
            ->from(['or' => 'sales_order'], ['ecpay_invoice_tag', 'increment_id'])
            ->where('entity_id = ?', $subOrderId);
        $orderData = $this->_conn->fetchRow($sqlOrder);
        if((int)$orderData['ecpay_invoice_tag'] != 1){
            $parentOrder = $this->orderManagement->getParentOrder(true, (int)$subOrderId);
            $this->errorMessages[] = __('Sorry, there was an operation error. Please try again later or contact customer service. Order Number:{%1（%2）}', $orderData['increment_id'], $parentOrder->getIncrementId());
        }
    }
}
