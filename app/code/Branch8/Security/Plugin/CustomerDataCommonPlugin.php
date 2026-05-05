<?php
declare(strict_types=1);

namespace Branch8\Security\Plugin;

use Branch8\Security\CustomerData\FormKeySection;
use Branch8\Customer\CustomerData\Common as CustomerDataCommon;

class CustomerDataCommonPlugin
{
    /**
     * @var FormKeySection
     */
    protected $formKeySection;

    /**
     * @param FormKeySection $formKeySection
     */
    public function __construct(FormKeySection $formKeySection)
    {
        $this->formKeySection = $formKeySection;
    }

    /**
     * @param CustomerDataCommon $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(CustomerDataCommon $subject, array $result)
    {
        $result['b8_form_key'] = $this->formKeySection->getSectionData();
        return $result;
    }
}
