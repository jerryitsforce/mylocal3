<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Model\Config\Source;

use Branch8\Rma\Model\Actions\ReasonsByTags;
use Branch8\RmaAdminUi\Helper\Logger as CustomLogger;

class Reasons implements \Magento\Eav\Model\Entity\Attribute\Source\SourceInterface,
    \Magento\Framework\Option\ArrayInterface
{
    private ReasonsByTags $reasonByTags;

    private $options = null;

    /**
     * @var CustomLogger $logger
     */
    public $logger;


    /**
     * @param ReasonsByTags $reasonsByTags
     * @param CustomLogger $logger
     */
    public function __construct(
        ReasonsByTags $reasonsByTags,
        CustomLogger $logger
    )
    {
        $this->reasonByTags = $reasonsByTags;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function getAllOptions()
    {
        if (null === $this->options) {
            $this->options = [];
            try {
                $reasons = $this->reasonByTags->find(ReasonsByTags::BACKEND_TAG);
                foreach ($reasons as $reason) {
                    $this->options[] = [
                        'value' => $reason['id'],
                        'label' => $reason['reason']
                    ];
                }
               /* array_unshift($this->options,
                    ['label' => __('Please Select'), 'value' => '']);*/
            } catch (\Exception $exception) {
                $this->logger->critical($exception);
            }
        }
        return $this->options;
    }

    /**
     * @param $value
     * @return false|mixed
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
}
