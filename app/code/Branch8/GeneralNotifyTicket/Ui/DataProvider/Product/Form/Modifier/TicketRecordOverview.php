<?php

namespace Branch8\GeneralNotifyTicket\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Form;
use Magento\Framework\UrlInterface;
use Magento\Framework\Module\Manager as ModuleManager;

class TicketRecordOverview extends AbstractModifier
{
    public const GROUP_CONTENT = 'content';
    public const SORT_ORDER = 30;
    public const LINK_TYPE = 'associated';
    /**
     * @var LocatorInterface
     * @since 100.1.0
     */
    protected $locator;
    /**
     * @var UrlInterface
     * @since 100.1.0
     */
    protected $urlBuilder;
    /**
     * @var ModuleManager
     */
    private $moduleManager;
    /**
     * @param LocatorInterface $locator
     * @param UrlInterface $urlBuilder
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $urlBuilder,
        ModuleManager $moduleManager
    ) {
        $this->locator = $locator;
        $this->urlBuilder = $urlBuilder;
        $this->moduleManager = $moduleManager;
    }
    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function modifyMeta(array $meta)
    {
        if (!$this->locator->getProduct()->getId() || !$this->moduleManager->isOutputEnabled('Branch8_GeneralNotifyTicket')) {
            return $meta;
        }

        $meta['general_notify_ticket_record_overview'] = [
            'children' => [
                'general_notify_ticket_record_overview' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => true,
                                'componentType' => 'insertListing',
                                'dataScope' => 'general_notify_ticket_record_overview',
                                'externalProvider' => 'general_notify_ticket_record_overview.general_notify_ticket_record_overview_data_source',
                                'selectionsProvider' => 'general_notify_ticket_record_overview.general_notify_ticket_record_overview.product_columns.ids',
                                'ns' => 'general_notify_ticket_record_overview',
                                // 'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => false,
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'productId' => '${ $.provider }:data.product.current_product_id',
                                    '__disableTmpl' => ['productId' => false],
                                ],
                                'exports' => [
                                    'productId' => '${ $.externalProvider }:params.current_product_id',
                                    '__disableTmpl' => ['productId' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('General notify ticket record overview'),
                        'collapsible' => true,
                        'opened' => false,
                        'componentType' => Form\Fieldset::NAME,
                        'sortOrder' =>
                            $this->getNextGroupSortOrder(
                                $meta,
                                static::GROUP_CONTENT,
                                static::SORT_ORDER
                            ),
                    ],
                ],
            ],
        ];

        return $meta;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function modifyData(array $data)
    {
        $flagProductId = $this->locator->getProduct()->getId();
        $data[$flagProductId][self::DATA_SOURCE_DEFAULT]['current_product_id'] = $flagProductId;
        return $data;
    }
}
