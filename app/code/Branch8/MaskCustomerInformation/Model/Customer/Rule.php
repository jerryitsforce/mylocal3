<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Model\Customer;

use Branch8\MaskCustomerInformation\Model\RuleInterface;
use Branch8\MaskInformation\Model\MaskRulesComposite;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Backend\Model\Auth\Session;

class Rule implements RuleInterface
{
    private RuleConfig $ruleConfig;

    private MaskRulesComposite $rulesComposite;
    private TimezoneInterface $timezone;
    private StoreInterface $store;
    private Session $authSession;

    /**
     * @param RuleConfig $ruleConfig
     * @param MaskRulesComposite $rulesComposite
     * @param TimezoneInterface $timezone
     * @param StoreInterface $store
     * @param Session $authSession
     */
    public function __construct(
        RuleConfig         $ruleConfig,
        MaskRulesComposite $rulesComposite,
        TimezoneInterface  $timezone,
        StoreInterface     $store,
        Session            $authSession
    )
    {
        $this->timezone = $timezone;
        $this->ruleConfig = $ruleConfig;
        $this->rulesComposite = $rulesComposite;
        $this->store = $store;
        $this->authSession = $authSession;
    }

    /**
     * @param array $data
     * @return array
     */
    public function apply(array $data): array
    {
        $rules = $this->ruleConfig->getRules();
        $rules['dob'] = 'birthday';
        $checkEn = false;
        if ($this->store->getLocaleCode() == 'en_US') {
            $rules['dob'] = 'birthday_en';
            $checkEn = true;
        }
        if ($this->authSession->isLoggedIn() && $this->authSession->getUser()->getInterfaceLocale() == 'en_US') {
            $rules['dob'] = 'birthday_en';
            $checkEn = true;
        }
        foreach ($data as $field => &$value) {
            if (empty($rules[$field]) || is_object($value)) {
                continue;
            }
            $rule = $rules[$field];
            if ($field === 'dob' && $value && ($value !== "0000-00-00 00:00:00")) {
                if ($checkEn) {
                    $date = $this->timezone->date(new \DateTime($value))->format('m/d/Y');
                } else {
                    $date = $this->timezone->date(new \DateTime($value))->format('Y/m/d');
                }
                $data[$field] = $this->rulesComposite->mask($rule, $date);
            } elseif ($field === 'street' && is_array($value)) {
                $value[0] = $this->rulesComposite->mask($rule, $value[0]);
                $data[$field] = $value;
            } else {
                $data[$field] = $this->rulesComposite->mask($rule, $value);
            }
        }
        return $data;
    }

    /**
     * @return string[]
     */
    public function getRuleConfig(): array
    {
        return $this->ruleConfig->getRules();
    }
}
