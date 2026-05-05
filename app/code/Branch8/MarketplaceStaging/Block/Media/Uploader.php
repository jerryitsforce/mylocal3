<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Block\Media;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Image\Adapter\UploadConfigInterface;
use Branch8\MarketplaceStaging\Model\Image\UploadResizeConfigInterface;

/**
 * Adminhtml media library uploader
 * @api
 * @since 100.0.2
 */
class Uploader extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_config;

    /**
     * @var string
     */
    protected $_template = 'Branch8_MarketplaceStaging::media/uploader.phtml';

    /**
     * @var \Magento\Framework\File\Size
     */
    protected $_fileSizeService;

    /**
     * @var Json
     */
    private $jsonEncoder;

    /**
     * @var UploadResizeConfigInterface
     */
    private $imageUploadConfig;

    /**
     * @var UploadConfigInterface
     * @deprecated 101.0.1
     * @see UploadResizeConfigInterface
     */
    private $imageConfig;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @param \Branch8\MarketplaceStaging\Block\Widget\Context $context
     * @param \Magento\Framework\File\Size $fileSize
     * @param array $data
     * @param Json $jsonEncoder
     * @param UploadConfigInterface $imageConfig
     * @param UploadResizeConfigInterface $imageUploadConfig
     */
    public function __construct(
        \Branch8\MarketplaceStaging\Block\Widget\Context $context,
        \Magento\Framework\File\Size $fileSize,
        array $data = [],
        Json $jsonEncoder = null,
        UploadConfigInterface $imageConfig = null,
        UploadResizeConfigInterface $imageUploadConfig = null
    ) {
        $this->_fileSizeService = $fileSize;
        $this->jsonEncoder = $jsonEncoder ?: ObjectManager::getInstance()->get(Json::class);
        $this->imageConfig = $imageConfig
            ?: ObjectManager::getInstance()->get(UploadConfigInterface::class);
        $this->imageUploadConfig = $imageUploadConfig
            ?: ObjectManager::getInstance()->get(UploadResizeConfigInterface::class);
        $this->mathRandom = $context->getMathRandom();
        parent::__construct($context, $data);
    }

    /**
     * Initialize block.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId($this->getId() . '_Uploader');

        $uploadUrl = $this->_urlBuilder->getUrl('adminhtml/*/upload');
        $this->getConfig()->setUrl($uploadUrl);
        $this->getConfig()->setParams(['form_key' => $this->getFormKey()]);
        $this->getConfig()->setFileField('file');
        $this->getConfig()->setFilters(
            [
                'images' => [
                    'label' => __('Images (.gif, .jpg, .png)'),
                    'files' => ['*.gif', '*.jpg', '*.png'],
                ],
                'media' => [
                    'label' => __('Media (.avi, .flv, .swf)'),
                    'files' => ['*.avi', '*.flv', '*.swf'],
                ],
                'all' => ['label' => __('All Files'), 'files' => ['*.*']],
            ]
        );
    }

    /**
     * Get file size
     *
     * @return \Magento\Framework\File\Size
     */
    public function getFileSizeService()
    {
        return $this->_fileSizeService;
    }

    /**
     * Get Image Upload Maximum Width Config.
     *
     * @return int
     * @since 100.2.7
     */
    public function getImageUploadMaxWidth()
    {
        return $this->imageUploadConfig->getMaxWidth();
    }

    /**
     * Get Image Upload Maximum Height Config.
     *
     * @return int
     * @since 100.2.7
     */
    public function getImageUploadMaxHeight()
    {
        return $this->imageUploadConfig->getMaxHeight();
    }

    /**
     * Prepares layout and set element renderer
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->addPageAsset('jquery/fileUploader/css/jquery.fileupload-ui.css');
        return parent::_prepareLayout();
    }

    /**
     * Retrieve uploader js object name
     *
     * @return string
     */
    public function getJsObjectName()
    {
        return $this->getHtmlId() . 'JsObject';
    }

    /**
     * Retrieve config json
     *
     * @return string
     */
    public function getConfigJson()
    {
        return $this->jsonEncoder->encode($this->getConfig()->getData());
    }

    /**
     * Retrieve config object
     *
     * @return \Magento\Framework\DataObject
     */
    public function getConfig()
    {
        if (null === $this->_config) {
            $this->_config = new \Magento\Framework\DataObject();
        }

        return $this->_config;
    }

    /**
     * Retrieve full uploader SWF's file URL
     * Implemented to solve problem with cross domain SWFs
     * Now uploader can be only in the same URL where backend located
     *
     * @param string $url url to uploader in current theme
     * @return string full URL
     */
    public function getUploaderUrl($url)
    {
        return $this->_assetRepo->getUrl($url);
    }/**
 * Get ID
 *
 * @return string
 */
    public function getId()
    {
        if (null === $this->getData('id')) {
            $this->setData('id', $this->mathRandom->getUniqueHash('id_'));
        }
        return $this->getData('id');
    }

    /**
     * Get HTML ID with specified suffix
     *
     * @param string $suffix
     * @return string
     */
    public function getSuffixId($suffix)
    {
        return "{$this->getId()}_{$suffix}";
    }

    /**
     * Get HTML ID
     *
     * @return string
     */
    public function getHtmlId()
    {
        return $this->getId();
    }

    /**
     * Get current url
     *
     * @param array $params url parameters
     * @return string current url
     */
    public function getCurrentUrl($params = [])
    {
        if (!isset($params['_current'])) {
            $params['_current'] = true;
        }
        return $this->getUrl('*/*/*', $params);
    }

    /**
     * Create button and return its html
     *
     * @param string $label
     * @param string $onclick
     * @param string $class
     * @param string $buttonId
     * @param array $dataAttr
     * @return string
     */
    public function getButtonHtml($label, $onclick, $class = '', $buttonId = null, $dataAttr = [])
    {
        return $this->getLayout()->createBlock(
            \Branch8\MarketplaceStaging\Block\Widget\Button::class
        )->setData(
            [
                'label' => $label,
                'onclick' => $onclick,
                'class' => $class,
                'type' => 'button',
                'id' => $buttonId
            ]
        )->setDataAttribute(
            $dataAttr
        )->toHtml();
    }
}
