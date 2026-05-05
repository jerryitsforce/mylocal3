<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\AdvancedPermissions\Ui\Component\Listing\Column;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\BooleanUtils;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class InvoiceDate extends Column
{
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var BooleanUtils
     */
    private $booleanUtils;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var DataBundle
     */
    private $dataBundle;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TimezoneInterface $timezone
     * @param BooleanUtils $booleanUtils
     * @param array $components
     * @param array $data
     * @param ResolverInterface|null $localeResolver
     * @param DataBundle|null $dataBundle
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        TimezoneInterface $timezone,
        BooleanUtils $booleanUtils,
        array $components = [],
        array $data = [],
        ResolverInterface $localeResolver = null,
        DataBundle $dataBundle = null
    ) {
        $this->timezone = $timezone;
        $this->booleanUtils = $booleanUtils;
        $this->localeResolver = $localeResolver ?? ObjectManager::getInstance()->get(ResolverInterface::class);
        $this->locale = $this->localeResolver->getLocale();
        $this->dataBundle = $dataBundle ?? ObjectManager::getInstance()->get(DataBundle::class);
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     * @since 101.1.1
     */
    public function prepare()
    {
        $config = $this->getData('config');
        if (isset($config['filter'])) {
            $config['filter'] = [
                'filterType' => 'dateRange',
                'templates' => [
                    'date' => [
                        'options' => [
                            'dateFormat' => $config['dateFormat'] ?? $this->timezone->getDateFormatWithLongYear()
                        ]
                    ]
                ]
            ];
        }

        $localeData = $this->dataBundle->get($this->locale);
        /** @var \ResourceBundle $monthsData */
        $monthsData = $localeData['calendar']['gregorian']['monthNames'];
        $months = array_values(iterator_to_array($monthsData['format']['wide']));
        $monthsShort = array_values(
            iterator_to_array(
                null !== $monthsData->get('format')->get('abbreviated')
                    ? $monthsData['format']['abbreviated']
                    : $monthsData['format']['wide']
            )
        );

        $config['storeLocale'] = $this->locale;
        $config['calendarConfig'] = [
            'months' => $months,
            'monthsShort' => $monthsShort,
        ];
        if (!isset($config['dateFormat'])) {
            $config['dateFormat'] = $this->timezone->getDateTimeFormat(\IntlDateFormatter::MEDIUM);
        }
        $this->setData('config', $config);

        parent::prepare();
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    if (!isset($item[$fieldName])) {
                        $item[$fieldName] = '';
                        try {
                            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
                            $connection = $resource->getConnection();

                            $table = $resource->getTableName('sales_invoice_grid');

                            $invoiceInfo = $connection->fetchAll(
                                $connection->select()
                                    ->from($table, [
                                        'invoice_status' => 'state',
                                        'invoice_modification_date' => 'updated_at'
                                    ])
                                    ->where('order_id = ?', (int)$item['entity_id'])
                            );
                            if (!empty($invoiceInfo)) {
                                $item['invoice_status'] = $invoiceInfo[0]['invoice_status'] ?? '';
                                $item['invoice_modification_date'] = $invoiceInfo[0]['invoice_modification_date'] ?? '';
                            }

                        } catch (\Exception $e) {
                        }
                    }
                    if (isset($item[$fieldName]) && $fieldName == 'invoice_modification_date'
                        && $item[$fieldName] !== "0000-00-00 00:00:00"
                    ) {
                        $date = $this->timezone->date(new \DateTime($item[$fieldName]));
                        $timezone = isset($this->getConfiguration()['timezone'])
                            ? $this->booleanUtils->convert($this->getConfiguration()['timezone'])
                            : true;
                        if (!$timezone) {
                            $date = new \DateTime($item[$fieldName]);
                        }
                        $item[$fieldName] = $date->format('Y-m-d H:i:s');
                    }
                }
            }
        }

        return $dataSource;
    }
}
