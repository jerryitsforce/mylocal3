<?php

namespace Branch8\MaskCustomerInformation\Model;

interface RuleConfigInterface
{
    /**
     * @return []
     */
    public function getRules();

    /**
     * Get Disable Editor Fields
     * @return mixed
     */
    public function getDisableEditorFields();
}
