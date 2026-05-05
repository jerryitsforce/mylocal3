<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       12/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model\Actions;

use Branch8\MarketPlaceProductDiscussion\Model\Message;

class MessageRawConverter
{
    private \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
        $this->timezone = $timezone;
    }

    /**
     * @param Message $message
     * @return array
     */
    public function convert(Message $message)
    {
        return  [
            'id' => $message->getId(),
            'author_id' => $message->getAuthorId(),
            'author_type' => $message->getAuthorType(),
            'title' => $message->getMessage(),
            'message' => $message->getMessage(),
            'created_at' => $this->formatDate($message->getCreatedAt()),
            'parent_id' => $message->getThreadId(),
            'updated_at'=>$this->formatDate($message->getUpdatedAt())
        ];
    }
    /**
     * @param $date
     * @return string
     */
    private function formatDate($date)
    {
        return $this->timezone->formatDateTime(
            $date,
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            null,
            null,
            'Y/MM/dd'
        );
    }

}
