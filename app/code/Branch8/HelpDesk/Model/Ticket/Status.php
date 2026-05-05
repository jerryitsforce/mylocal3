<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    const UNPROCESSED = 1;

    const PROCESSING = 2;
    const PROCESSED = 3;
    const READ = 4;
    const UNREAD = 5;
    const CSR_REPLIED = 10;
    const XML_PATH_NOTIFY_STATUES = 'helpdesk/email_settings/customer_setting/customer_new_status_notify';
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * ToOptionArray
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('UnRead'), 'value' => self::UNREAD],
            ['label' => __('Read'), 'value' => self::READ],
            ['label' => __('Unprocessed'), 'value' => self::UNPROCESSED],
            ['label' => __('Processing'), 'value' => self::PROCESSING],
            ['label' => __('Processed'), 'value' => self::PROCESSED]
        ];
    }

    /**
     * toOptions
     * @return array
     */
    public function toOptions()
    {
        return [
            self::UNREAD => __('UnRead'),
            self::READ => __('Read'),
            self::UNPROCESSED => __('Unprocessed'),
            self::PROCESSING => __('Processing'),
            self::PROCESSED => __('Processed'),
            self::CSR_REPLIED => __('Customer Service Replied')
        ];
    }

    /**
     * getStatusLabel
     * @param $status
     * @return mixed|string
     */
    public function getStatusLabel($status)
    {
        $options = $this->toOptions();
        return isset($options[$status]) ? $options[$status]->render() : '';
    }

    /**
     * isStatusNeedToNotify
     * @param $status
     * @return bool
     */
    public function isStatusNeedToNotify($status)
    {
        $statuses = (string)$this->scopeConfig->getValue(self::XML_PATH_NOTIFY_STATUES);
        try {
            $statuses = explode(',', $statuses);
        } catch (\Exception $exception) {
            $statuses = [];
        }
        return in_array((string)$status, $statuses);
    }

    /**
     * @param $status
     * @return int
     */
    public static function frontendStatusResolve($status)
    {
        switch ($status) {
            case Status::READ:
            case Status::UNREAD:
                $frontendStatus = Status::UNPROCESSED;
                break;
            case Status::PROCESSED:
            case Status::PROCESSING:
                $frontendStatus = Status::CSR_REPLIED;
                break;
            default:
                $frontendStatus = $status;
        }
        return $frontendStatus;
    }
}
