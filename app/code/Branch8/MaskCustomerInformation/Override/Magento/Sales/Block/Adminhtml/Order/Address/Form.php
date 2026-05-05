<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Override\Magento\Sales\Block\Adminhtml\Order\Address;

use Branch8\MaskCustomerInformation\Model\PermissionInterface;
use Branch8\MaskCustomerInformation\Model\RuleManagement;
use Branch8\MaskInformation\Model\MaskRulesComposite;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 *
 */
class Form extends \Magento\Sales\Block\Adminhtml\Order\Address\Form
{
    private $permission;

    private $maskRulesComposite;

    private $ruleManagement;

    /**
     * @param PermissionInterface $permission
     * @param RuleManagement $ruleManagement
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Customer\Model\Metadata\FormFactory $customerFormFactory
     * @param \Magento\Customer\Model\Options $options
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressService
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        PermissionInterface                               $permission,
        RuleManagement                                    $ruleManagement,
        MaskRulesComposite                                $maskRulesComposite,
        \Magento\Backend\Block\Template\Context           $context,
        \Magento\Backend\Model\Session\Quote              $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create            $orderCreate,
        PriceCurrencyInterface                            $priceCurrency,
        \Magento\Framework\Data\FormFactory               $formFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Magento\Directory\Helper\Data                    $directoryHelper,
        \Magento\Framework\Json\EncoderInterface          $jsonEncoder,
        \Magento\Customer\Model\Metadata\FormFactory      $customerFormFactory,
        \Magento\Customer\Model\Options                   $options,
        \Magento\Customer\Helper\Address                  $addressHelper,
        \Magento\Customer\Api\AddressRepositoryInterface  $addressService,
        \Magento\Framework\Api\SearchCriteriaBuilder      $criteriaBuilder,
        \Magento\Framework\Api\FilterBuilder              $filterBuilder,
        \Magento\Customer\Model\Address\Mapper            $addressMapper,
        \Magento\Framework\Registry                       $registry,
        array                                             $data = []
    )
    {
        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $formFactory,
            $dataObjectProcessor,
            $directoryHelper,
            $jsonEncoder,
            $customerFormFactory,
            $options,
            $addressHelper,
            $addressService,
            $criteriaBuilder,
            $filterBuilder,
            $addressMapper,
            $registry,
            $data
        );
        $this->_coreRegistry = $registry;
        $this->permission = $permission;
        $this->ruleManagement = $ruleManagement;
        $this->maskRulesComposite = $maskRulesComposite;
    }

    /**
     * @return $this|Form
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();
        if (!$this->permission->canView()) {
            /**
             * @var $form \Magento\Framework\Data\Form
             */
            $form = $this->_form;
            foreach (array_keys($this->disableFields()) as $field) {
                $formField = $form->getElement($field);
                if ($formField) {
                    $formField->setType('note');
                    $formField->setReadonly(true, true);
                }
            }
        }
        return $this;
    }

    /**
     * @return array|mixed|null
     */
    public function getFormValues()
    {
        $address = $this->_getAddress()->getData();
        if ($this->permission->canView()) {
            return $address;
        }
        $fieldRules = $this->disableFields();
        $fields = array_keys($fieldRules);
        foreach ($address as $key => &$value) {
            if (in_array($key, $fields)) {
                $address[$key] = $this->maskRulesComposite->mask($fieldRules[$key], $value);
            }
        }
        return $address;
    }

    /**
     * Get field and own rule
     * @return string[]
     */
    private function disableFields()
    {
        return [
            'firstname' => 'firstname',
            'street' => 'address',
            'telephone' => 'phone'
        ];
    }
}
