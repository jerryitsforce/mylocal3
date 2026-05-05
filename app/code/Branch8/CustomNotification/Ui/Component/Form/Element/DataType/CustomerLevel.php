<?php

namespace Branch8\CustomNotification\Ui\Component\Form\Element\DataType;

use Branch8\CustomNotification\Model\ConfigData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Sanitizer;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Ui\Component\Form\Field;

/**
 * Class Media
 */
class CustomerLevel extends Field
{
    const ONEIDUPLOAD_NOTIFICATIONTYPE_OPTIONS = [
        'news'
    ];

    private $resourceConnection;

    private $configData;

    private $url;

    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        StoreManager       $storeManager,
        ConfigData         $configData,
        UrlInterface       $url,
        ResourceConnection $resourceConnection,
        array              $components = [],
        array              $data = []
    )
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->storeManager = $storeManager;
        $this->configData = $configData;
        $this->resourceConnection = $resourceConnection;
        $this->url = $url;
    }

    /**
     * Get component name
     *
     * @return string
     */
    public function getComponentName()
    {
        return 'form.' . $this->wrappedComponent->getComponentName();
    }

    /**
     * Prepare component configuration
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepare()
    {
        parent::prepare();
        $data = array_replace_recursive(
            $this->getData(),
            [
                'config' => [
                    'OneIdCustomerGroup' => $this->configData->getOneIdGroup(),
                    'ONEIDUPLOAD_NOTIFICATIONTYPE_OPTIONS' => array_values($this->configData->getNotAvailableNotifications()),
                    'NOTIFICATIONTYPE_PERMIT' => array_values($this->configData->getNotAvailableNotifications()),
                    'ONEIDUPLOAD_NA_NOTIFICATIONTYPE_TEXT' => $this->getNotificatonForOneIdUpload()
                ]
            ]
        );
        $this->setData($data);
    }

    /**
     * @return array
     */
    private function getNotificatonForOneIdUpload()
    {
        $columns = ['name'];
        $select = $this->resourceConnection->getConnection()->select();
        $select->from('magenest_notification_type', $columns)
            ->where('default_type  IN (?)',
                $this->configData->getNotAvailableNotifications()
            );
        return $this->resourceConnection->getConnection()->fetchCol($select);
    }
}
