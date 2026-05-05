<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Ui\Listing\Columns;

use Branch8\MaskCustomerInformation\Model\PermissionInterface;
use Branch8\MaskInformation\Model\Rules\BirthDay;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\BooleanUtils;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Date extends \Magento\Ui\Component\Listing\Columns\Date
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

    private $permission;

    private $birthDay;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TimezoneInterface $timezone
     * @param BooleanUtils $booleanUtils
     * @param PermissionInterface $permission
     * @param BirthDay $birthDay
     * @param array $components
     * @param array $data
     * @param ResolverInterface|null $localeResolver
     * @param DataBundle|null $dataBundle
     */
    public function __construct(
        ContextInterface    $context,
        UiComponentFactory  $uiComponentFactory,
        TimezoneInterface   $timezone,
        BooleanUtils        $booleanUtils,
        PermissionInterface $permission,
        BirthDay            $birthDay,
        array               $components = [],
        array               $data = [],
        ResolverInterface   $localeResolver = null,
        DataBundle          $dataBundle = null
    )
    {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $timezone,
            $booleanUtils,
            $components,
            $data,
            $localeResolver,
            $dataBundle
        );
        $this->permission = $permission;
        $this->timezone = $timezone;
        $this->booleanUtils = $booleanUtils;
        $this->localeResolver = $localeResolver ?? ObjectManager::getInstance()->get(ResolverInterface::class);
        $this->locale = $this->localeResolver->getLocale();
        $this->dataBundle = $dataBundle ?? ObjectManager::getInstance()->get(DataBundle::class);
        $this->birthDay = $birthDay;
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
        if (!$this->hasPermission()) {
            $config['component'] = 'Branch8_MaskCustomerInformation/js/grid/columns/mask-date-column';
            $config['secure'] = true;
        }
        $this->setData('config', $config);
        parent::prepare();
    }

    /**
     * @return bool
     */
    private function hasPermission()
    {
        return $this->permission->canView();
    }

    /**
     * @param array $dataSource
     * @return array
     * @throws \Exception
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$this->getData('name')])
                    && $item[$this->getData('name')] !== "0000-00-00 00:00:00"
                ) {
                    if ($this->hasPermission()) {
                        $date = $this->timezone->date(new \DateTime($item[$this->getData('name')]));
                        $timezone = isset($this->getConfiguration()['timezone'])
                            ? $this->booleanUtils->convert($this->getConfiguration()['timezone'])
                            : true;
                        if (!$timezone) {
                            $date = new \DateTime($item[$this->getData('name')]);
                        }
                        $item[$this->getData('name')] = $date->format('Y-m-d H:i:s');
                    }
                }
            }
        }

        return $dataSource;
    }
}
