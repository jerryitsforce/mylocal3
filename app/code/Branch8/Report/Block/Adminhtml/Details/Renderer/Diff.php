<?php

declare(strict_types=1);

namespace Branch8\Report\Block\Adminhtml\Details\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\SerializerInterface;

class Diff extends AbstractRenderer
{
    /**
     * @var Escaper
     */
    private Escaper $escaper;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * Diff constructor.
     *
     * @param Context $context
     * @param array $data
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        Context             $context,
        array               $data = [],
        SerializerInterface $serializer = null
    ) {
        parent::__construct($context, $data);
        $this->escaper = $context->getEscaper();
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function render(DataObject $row)
    {
        $html = '-';
        $columnData = $row->getData($this->getColumn()->getIndex());
        $specialFlag = false;
        try {
            $dataArray = !is_array($columnData) ? $this->serializer->unserialize($columnData) : $columnData;
            if (is_bool($dataArray)) {
                $html = $dataArray ? 'true' : 'false';
            } elseif (is_array($dataArray)) {
                if (isset($dataArray['__no_changes'])) {
                    $html = __('No changes');
                    $specialFlag = true;
                }
                if (isset($dataArray['__was_deleted'])) {
                    $html = __('The item was deleted');
                    $specialFlag = true;
                }
                if (isset($dataArray['__was_created'])) {
                    $html = __('N/A');
                    $specialFlag = true;
                }

                if (!$specialFlag) {
                    $html = '<dl class="list-parameters">';
                    foreach ($dataArray as $key => $value) {
                        if (!is_array($value)) {
                            if (is_bool($value)) {
                                $value = $value === false ? 'false' : 'true';
                            } elseif ($value === null) {
                                $value = '';
                            }
                            $html .= '<dt class="parameter value">' . (!is_numeric($key) ? ($this->escaper->escapeHtml($key) . ': ') : '')
                                . $this->escaper->escapeHtml($value) . '</dt>';
                        } elseif ($key == 'time') {
                            $html .= '<dt class="parameter">' . $this->escaper->escapeHtml($key) . '</dt>';
                            $html .= '<dd class="value">' . $this->escaper->escapeHtml(implode(":", $value)) . '</dd>';
                        } else {
                            $html .= '<dt class="parameter">' . $this->escaper->escapeHtml($key) . '</dt>';
                            if ($value) {
                                foreach ($value as $k => $complexValue) {
                                    if (is_array($complexValue)) {
                                        $complexValue = $this->serializer->serialize($complexValue);
                                    } elseif (is_bool($complexValue)) {
                                        $complexValue = $complexValue === false ? 'false' : 'true';
                                    }
                                    $html .= '<dd class="value">' . (!is_numeric($k) ? ($this->escaper->escapeHtml($k) . ': ') : '')
                                        . $this->escaper->escapeHtml($complexValue) . '</dd>';
                                }
                            } else {
                                $html .= '<dd class="value">N/A</dd>';
                            }
                        }
                    }
                    $html .= '</dl>';
                }
            } else {
                $html = $columnData;
            }
        } catch (\Exception $e) {
            $html = $columnData;
        }
        return $html;
    }
}
