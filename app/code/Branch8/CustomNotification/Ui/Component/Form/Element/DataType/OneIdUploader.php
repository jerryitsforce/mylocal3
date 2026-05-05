<?php

namespace Branch8\CustomNotification\Ui\Component\Form\Element\DataType;

use Branch8\CustomNotification\Model\ConfigData;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class Media
 */
class OneIdUploader extends \Magento\Ui\Component\Form\Element\DataType\Media
{
    private $configData;

    private $url;

    /**
     * @param ContextInterface $context
     * @param ConfigData $configData
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        ConfigData       $configData,
        UrlInterface     $url,
        array            $components = [],
        array            $data = []
    )
    {
        $this->configData = $configData;
        $this->url = $url;
        parent::__construct($context, $components, $data);
    }

    /**
     * @return void
     */
    public function prepare()
    {
        parent::prepare();
        $data = array_replace_recursive(
            $this->getData(),
            [
                'config' => [
                    'OneIdCustomerGroup' => $this->configData->getOneIdGroup(),
                    'downLoadSampleFileLinks' => $this->url->getUrl('notibox/OneId/SampleFiles')
                ]
            ]
        );
        $this->setData($data);
    }
}
