<?php
declare(strict_types=1);

namespace Branch8\HelpDeskRecaptcha\Plugin\Branch8\HelpDesk\Block\Ticket;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\UiConfigResolverInterface;

class FormPlugin
{
    /**
     * @var UiConfigResolverInterface
     */
    private $captchaUiConfigResolver;

    /**
     * @var IsCaptchaEnabledInterface
     */
    private $isCaptchaEnabled;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param UiConfigResolverInterface $captchaUiConfigResolver
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     * @param Json $serializer
     */
    public function __construct(
        UiConfigResolverInterface $captchaUiConfigResolver,
        IsCaptchaEnabledInterface $isCaptchaEnabled,
        Json                      $serializer
    )
    {
        $this->captchaUiConfigResolver = $captchaUiConfigResolver;
        $this->isCaptchaEnabled = $isCaptchaEnabled;
        $this->serializer = $serializer;
    }

    /**
     * @param \Branch8\HelpDesk\Block\Ticket\Form $subject
     * @param $result
     * @return bool|string
     * @throws InputException
     */
    public function afterGetJsLayout(\Branch8\HelpDesk\Block\Ticket\Form $subject, $result)
    {
        $layout = $this->serializer->unserialize($result);
        $key = 'branch8_helpdesk_ticket';

        if ($this->isCaptchaEnabled->isCaptchaEnabledFor($key)) {
            $layout['components']['ticketCreateForm']['children']['recaptcha']['settings']
                = $this->captchaUiConfigResolver->get($key);
            /**
             * <item name="component" xsi:type="string">Branch8_HelpDeskRecaptcha/js/submitTicketCaptcha</item>
             * <item name="displayArea" xsi:type="string">additional-login-form-fields</item>
             * <item name="reCaptchaId" xsi:type="string">recaptcha-helpdesk-ticket</item>
             * <item name="formId" xsi:type="string">branch8_helpdesk_ticket</item>
             */
            $layout['components']['ticketCreateForm']['children']['recaptcha']['component']='Branch8_HelpDeskRecaptcha/js/submitTicketCaptcha';
            $layout['components']['ticketCreateForm']['children']['recaptcha']['displayArea']='additional-login-form-fields';
            $layout['components']['ticketCreateForm']['children']['recaptcha']['reCaptchaId']='recaptcha-helpdesk-ticket';
            $layout['components']['ticketCreateForm']['children']['recaptcha']['formId']='branch8_helpdesk_ticket';
        } else {
            unset($layout['components']['ticketCreateForm']['children']['recaptcha']);
        }
        return $this->serializer->serialize($layout);
    }
}
