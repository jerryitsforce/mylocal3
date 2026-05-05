<?php
namespace Branch8\Customer\Plugin\Newsletter\Model;

use Magento\Newsletter\Model\Config as NewsletterConfig;

class Subscriber
{
    /**
     * @var NewsletterConfig
     */
    private $newsletterConfig;

    /**
     * @param NewsletterConfig $newsletterConfig
     */
    public function __construct(
        NewsletterConfig $newsletterConfig
    ){
        $this->newsletterConfig = $newsletterConfig;
    }

    /**
     * Sends out confirmation email
     *
     * @return $this
     */
    public function aroundSendConfirmationRequestEmail($subject, $process)
    {
        if ($this->newsletterConfig->isActive()) {
            return $process();
        }
    }

    /**
     * Sends out confirmation success email
     *
     * @return $this
     */
    public function aroundSendConfirmationSuccessEmail($subject, $process)
    {
        if ($this->newsletterConfig->isActive()) {
            return $process();
        }
    }

    /**
     * Sends out unsubscription email
     *
     * @return $this
     */
    public function aroundSendUnsubscriptionEmail($subject, $process)
    {
        if ($this->newsletterConfig->isActive()) {
            return $process();
        }
    }
}
