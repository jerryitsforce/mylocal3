<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Plugin\Magento\Customer\Model\Customer;

use Branch8\MaskCustomerInformation\Model\Customer\Rule;
use Branch8\MaskCustomerInformation\Model\Order\RuleConfig;
use Branch8\MaskCustomerInformation\Model\GlobalPermission;
use Branch8\MaskCustomerInformation\Model\PermissionInterface;
use Branch8\MaskCustomerInformation\Model\RuleManagement;
use Branch8\MaskInformation\Model\MaskRulesComposite;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\ArrayManager;

/**
 * @property GlobalPermission $permission
 * @property RuleConfig $ruleConfig
 */
class DataProviderWithDefaultAddressesPlugin
{
    private PermissionInterface $permission;
    private \Magento\Framework\Stdlib\ArrayManager $arrayManager;

    private RuleConfig $ruleConfig;
    private MaskRulesComposite $composite;
    private RequestInterface $request;
    private Rule $rule;
    private RuleManagement $maskRuleManagement;
    private MaskRulesComposite $maskRuleComposite;

    /**
     * @param PermissionInterface $permission
     * @param ArrayManager $arrayManager
     * @param Rule $rule
     * @param RuleManagement $management
     * @param MaskRulesComposite $maskRulesComposite
     * @param RequestInterface $request
     */
    public function __construct(
        PermissionInterface                    $permission,
        \Magento\Framework\Stdlib\ArrayManager $arrayManager,
        Rule                                   $rule,
        RuleManagement                         $management,
        MaskRulesComposite                     $maskRulesComposite,
        RequestInterface                       $request

    )
    {
        $this->rule = $rule;
        $this->permission = $permission;
        $this->request = $request;
        $this->maskRuleManagement = $management;
        $this->arrayManager = $arrayManager;
        $this->maskRuleComposite = $maskRulesComposite;
    }

    /**
     * @param $subject
     * @param $result
     * @return array
     */
    public function afterGetConfigData($subject, $result)
    {
        $result['canView'] = $this->permission->canView();
        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed|void
     */
    public function afterGetMeta($subject, $result)
    {
        if ($this->permission->canView()) {
            return $result;
        }
        $rules = $this->rule->getRuleConfig();
        if (empty($rules['dob'])) {
            $rules['dob'] = ['birthday'];
        }
        foreach ($rules as $field => $rule) {
            $result = $this->modifyFieldComponent($result, $field, $rule);
        }
        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @return array|mixed
     */
    public function afterGetData($subject, $result)
    {
        if ($this->permission->canView() || empty($this->request->getParam('id'))) {
            return $result;
        }
        $id = $this->request->getParam('id');
        if (isset($result[$id])) {
            if (isset($result[$id]['customer'])) {
                $tmpDob = '';
                $tmpInvoiceCarrier = '';
                $tmpEmail = '';
                if (isset($result[$id]['customer']['dob'])) {
                    $tmpDob = $result[$id]['customer']['dob'];
                }
                if (isset($result[$id]['customer']['invoice_carrier'])) {
                    $tmpInvoiceCarrier = $result[$id]['customer']['invoice_carrier'];
                }
                if (isset($result[$id]['customer']['email'])) {
                    $tmpEmail = $result[$id]['customer']['email'];
                }
                $customer = $this->rule->apply($result[$id]['customer']);
                if ($tmpDob) {
                    $customer['dob_clone'] = $customer['dob'];
                    $customer['dob'] = $tmpDob;
                }
                if ($tmpInvoiceCarrier) {
                    $customer['invoice_carrier_clone'] = $customer['invoice_carrier'];
                    $customer['invoice_carrier'] = $tmpInvoiceCarrier;
                }
                if ($tmpEmail) {
                    $customer['email_clone'] = $customer['email'];
                    $customer['email'] = $tmpEmail;
                }
                $result[$id]['customer'] = $customer;
            }
        }
        return $result;
    }

    /**
     * @param array $result
     * @param $fieldCode
     * @return array
     */
    private function modifyFieldComponent(array $result, $fieldCode)
    {
        $path = 'customer/children' . ArrayManager::DEFAULT_PATH_DELIMITER . $fieldCode;
        $path .= '/arguments/data/config';
        $config = $this->arrayManager->get($path, $result);
        if ($this->arrayManager->exists($path, $result)) {
            $default['disabled'] = 1;
            if ($fieldCode === 'group_id' && !empty($config['options'])) {
                $default['options'] = $this->modifyOptions('group_id', $config['options']);
            } elseif ($fieldCode !== 'dob') {
                $default['formElement'] = 'input';
                $default['elementTmpl'] = 'ui/form/element/text';
            }
            if ($fieldCode === 'dob' || $fieldCode === 'invoice_carrier' || $fieldCode === 'email') {
                $default['componentDisabled'] = true;
            }
            $result = $this->arrayManager->merge($path, $result, $default);
            if ($fieldCode === 'dob') {
                $newField = [];
                $newField['formElement'] = 'input';
                $newField['elementTmpl'] = 'ui/form/element/text';
                $newField['label'] = $result['customer']['children']['dob']['arguments']['data']['config']['label'];
                $newField['visible'] = 1;
                $newField['sortOrder'] = $result['customer']['children']['dob']['arguments']['data']['config']['sortOrder'] + 1;
                $newField['componentType'] = 'field';
                $result['customer']['children']['dob_clone']['arguments']['data']['config'] = $newField;
            }
            if ($fieldCode === 'invoice_carrier') {
                $newField = [];
                $newField['formElement'] = 'input';
                $newField['elementTmpl'] = 'ui/form/element/text';
                $newField['label'] = $result['customer']['children']['invoice_carrier']['arguments']['data']['config']['label'];
                $newField['visible'] = 1;
                $newField['sortOrder'] = $result['customer']['children']['invoice_carrier']['arguments']['data']['config']['sortOrder'] + 1;
                $newField['componentType'] = 'field';
                $result['customer']['children']['invoice_carrier_clone']['arguments']['data']['config'] = $newField;
            }
            if ($fieldCode === 'email') {
                $newField = [];
                $newField['formElement'] = 'input';
                $newField['elementTmpl'] = 'ui/form/element/text';
                $newField['label'] = $result['customer']['children']['email']['arguments']['data']['config']['label'];
                $newField['visible'] = 1;
                $newField['sortOrder'] = $result['customer']['children']['email']['arguments']['data']['config']['sortOrder'] + 1;
                $newField['componentType'] = 'field';
                $result['customer']['children']['email_clone']['arguments']['data']['config'] = $newField;
            }
        }
        return $result;
    }

    /**
     * @param $code
     * @param $options
     * @return mixed
     */
    private function modifyOptions($code, $options)
    {
        foreach ($options as &$option) {
            if (!$option['value']) {
                continue;
            }
            $option['label'] = $this->maskRuleComposite->mask($code, $option['label']);
        }
        return $options;
    }
}
