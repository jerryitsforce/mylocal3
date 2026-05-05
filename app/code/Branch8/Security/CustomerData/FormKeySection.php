<?php
declare(strict_types=1);

namespace Branch8\Security\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Data\Form\FormKey;

class FormKeySection implements SectionSourceInterface
{
    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @param FormKey $formKey
     */
    public function __construct(
        FormKey $formKey
    ) {
        $this->formKey = $formKey;
    }

    /**
     * @inheritdoc
     */
    public function getSectionData()
    {
        return [
            'form_key' => $this->formKey->getFormKey(),
        ];
    }
}
