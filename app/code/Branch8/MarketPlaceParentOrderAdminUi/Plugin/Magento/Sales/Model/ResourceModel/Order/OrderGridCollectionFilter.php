<?php declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Plugin\Magento\Sales\Model\ResourceModel\Order;

use DateTime;
use DateTimeInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class OrderGridCollectionFilter
{
    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timeZone;

    private RequestInterface $request;

    /**
     * Timezone converter interface
     *
     * @param TimezoneInterface $timeZone
     */
    public function __construct(
        TimezoneInterface $timeZone,
        RequestInterface $request
    ) {
        $this->timeZone = $timeZone;
        $this->request  = $request;
    }

    /**
     * Conditional column filters with timezone convertor interface
     *
     * @param  SearchResult $subject
     * @param  \Closure     $proceed
     * @param  string       $field
     * @param  string|null  $condition
     * @return SearchResult|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundAddFieldToFilter(
        SearchResult $subject,
        \Closure $proceed,
        $field,
        $condition = null
    ) {
        if ($field === 'created_at' || $field === 'order_created_at') {

            // Add prefix to field of filter when join multi tables
            if ($this->request->getParams()['namespace'] === "sales_parent_order_grid") {
                $field = "main_table.$field";
            }
            if ($this->request->getParams()['namespace'] === "sales_parent_gift_order_grid") {
                $field = "main_table.$field";
            }

            if ($this->request->getParams()['namespace'] === "customer_listing") {
                $field = "main_table.$field";
            }

            if (is_array($condition)) {
                foreach ($condition as $key => $value) {
                    if ($value = $this->isValidDate($value)) {
                        $condition[$key] = $value->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    }
                }
            }

            $fieldName = $subject->getConnection()->quoteIdentifier($field);
            $condition = $subject->getConnection()->prepareSqlCondition($fieldName, $condition);
            $subject->getSelect()->where($condition, null, Select::TYPE_CONDITION);

            return $subject;
        }

        return $proceed($field, $condition);
    }

    /**
     * Validate date string
     *
     * @param mixed $datetime
     * @return mixed
     */
    private function isValidDate(mixed $datetime): mixed
    {
        try {
            return $datetime instanceof DateTimeInterface
                ? $datetime : (is_string($datetime)
                    ? new DateTime($datetime, new \DateTimeZone($this->timeZone->getConfigTimezone())) : false);
        } catch (\Exception $e) {
            return false;
        }
    }
}
