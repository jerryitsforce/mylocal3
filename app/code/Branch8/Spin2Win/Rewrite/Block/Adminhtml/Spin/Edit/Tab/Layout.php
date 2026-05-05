<?php
namespace Branch8\Spin2Win\Rewrite\Block\Adminhtml\Spin\Edit\Tab;

class Layout extends \Webkul\SpinToWin\Block\Adminhtml\Spin\Edit\Tab\Layout{
    
    protected $_template = 'Webkul_SpinToWin::tab/layout.phtml';
    /**
     * URL
     *
     * @return string
     */
    public function getUploadUrl()
    {
        return $this->_urlInterface->getUrl('catalog/product_gallery/upload');
    }

    /**
     * Get path of uploaded images
     *
     * @return \Magento\Framework\UrlInterface
     */
    /**
     * Get Media Url
     *
     * @param mixed $filePath
     * @return \Magento\Framework\UrlInterface
     */
    public function getMediaUrl($filePath)
    {
        return $this->_urlBuilder->getBaseUrl([
            '_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ]).$filePath;
    }
}