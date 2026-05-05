<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Smtp
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Branch8\Smtp\Plugin\Mageplaza\Smtp\Model;

use Mageplaza\Smtp\Helper\Data;

class Log
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Log constructor.
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper            = $helper;
    }

    public function aroundSaveLog($subject, \Closure $proceed, $message, $status)
    {
        if ($this->helper->versionCompare('2.2.8')) {
            if ($message->getSubject()) {
                $subject->setSubject($message->getSubject());
            }

            $from = $message->getFrom();
            if (count($from)) {
                if (is_object($from)) {
                    $from->rewind();
                    $subject->setSender($from->current()->getName() . ' <' . $from->current()->getEmail() . '>');
                } elseif (isset($from[0])) {
                    $subject->setSender($from[0]->getName() . ' <' . $from[0]->getEmail() . '>');
                }
            }

            $toArr = [];
            foreach ($message->getTo() as $toAddr) {
                $toArr[] = $toAddr->getEmail();
            }
            $subject->setRecipient(implode(',', $toArr));

            $ccArr = [];
            foreach ($message->getCc() as $ccAddr) {
                $ccArr[] = $ccAddr->getEmail();
            }
            $subject->setCc(implode(',', $ccArr));

            $bccArr = [];
            foreach ($message->getBcc() as $bccAddr) {
                $bccArr[] = $bccAddr->getEmail();
            }
            $subject->setBcc(implode(',', $bccArr));

            if ($this->helper->versionCompare('2.3.3')) {
                $messageBody = quoted_printable_decode($message->getBodyText());
                $content     = htmlspecialchars($messageBody);
            } else {
                $content = htmlspecialchars($message->getBodyText());
            }
        } else {
            $headers = $message->getHeaders();

            if (isset($headers['Subject'][0])) {
                $subject->setSubject($headers['Subject'][0]);
            }

            if (isset($headers['From'][0])) {
                $subject->setSender($headers['From'][0]);
            }

            if (isset($headers['To'])) {
                $recipient = $headers['To'];
                if (isset($recipient['append'])) {
                    unset($recipient['append']);
                }
                $subject->setRecipient(implode(', ', $recipient));
            }

            if (isset($headers['Cc'])) {
                $cc = $headers['Cc'];
                if (isset($cc['append'])) {
                    unset($cc['append']);
                }
                $subject->setCc(implode(', ', $cc));
            }

            if (isset($headers['Bcc'])) {
                $bcc = $headers['Bcc'];
                if (isset($bcc['append'])) {
                    unset($bcc['append']);
                }
                $subject->setBcc(implode(', ', $bcc));
            }

            $body = $message->getBodyHtml();
            if (is_object($body)) {
                $content = htmlspecialchars($body->getRawContent());
            } else {
                $content = htmlspecialchars($message->getBody()->getRawContent());
            }
        }

        $subject->setEmailContent($content)
            ->setStatus($status)
            ->save();
    }
}
