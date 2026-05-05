<?php
namespace Branch8\Sales\Plugin\Framework\Mail;

use Branch8\Sales\Helper\Data;
use Magento\Framework\Mail\TemplateInterface;

class TemplatePlugin
{
    private Data $helper;

    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param TemplateInterface $subject
     * @param string $result
     * @return string
     */
    public function afterGetSubject(TemplateInterface $subject, string $result): string
    {
        $prefix = $this->helper->getEmailSubjectPrefix();
        return $prefix . $result;
    }

}
