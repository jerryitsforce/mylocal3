<?php
declare(strict_types=1);

namespace Branch8\MagentoUi\Override\Magento\Ui\Component\Filters\Type;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Filters\FilterModifier;

class Date extends \Magento\Ui\Component\Filters\Type\Date
{
    private $timezone;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param FilterBuilder $filterBuilder
     * @param FilterModifier $filterModifier
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        FilterBuilder      $filterBuilder,
        FilterModifier     $filterModifier,
        TimezoneInterface  $timezone,
        array              $components = [],
        array              $data = []
    )
    {

        parent::__construct(
            $context,
            $uiComponentFactory,
            $filterBuilder,
            $filterModifier,
            $components,
            $data
        );
        $this->timezone = $timezone;
        $this->uiComponentFactory = $uiComponentFactory;
        $this->filterBuilder = $filterBuilder;
        $this->filterData = $this->getContext()->getFiltersParams();
        $this->filterModifier = $filterModifier;
    }

    /**
     * @return void
     */
    protected function applyFilter()
    {
        if (isset($this->filterData[$this->getName()])) {
            $value = $this->filterData[$this->getName()];

            if (empty($value)) {
                return;
            }

            if (is_array($value)) {
                if (isset($value['from'])) {
                    $this->applyFilterByType(
                        'gteq',
                        $this->convertDatetime((string)$value['from'])
                    );
                }

                if (isset($value['to'])) {
                    $this->applyFilterByType(
                        'lteq',
                        $this->convertDatetime((string)$value['to'], 23, 59, 59)
                    );
                }
            } else {
                $this->applyFilterByType('eq', $this->convertDatetime((string)$value));
            }
        }
    }

    /**
     * @param string $value
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @return \DateTime|null
     */
    private function convertDatetime(string $value, int $hour = 0, int $minute = 0, int $second = 0): ?\DateTime
    {
        $timezone = $this->timezone->getConfigTimezone();
        $value = $this->getData('config/options/showsTime')
            ? $this->wrappedComponent->convertDatetime(
                $value,
                !$this->getData('config/skipTimeZoneConversion')
            )
            : $this->wrappedComponent->convertDate(
                $value,
                $hour,
                $minute,
                $second,
                !$this->getData('config/skipTimeZoneConversion')
            );
        if ($value instanceof \DateTime && $timezone == 'Asia/Taipei') {
            $value = $value->modify("-8 hours");
        }
        return $value;
    }
}
