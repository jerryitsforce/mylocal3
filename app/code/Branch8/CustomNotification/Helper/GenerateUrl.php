<?php

namespace Branch8\CustomNotification\Helper;

use Magento\Catalog\Helper\Data;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Escaper;

class GenerateUrl extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\UrlRewrite\Model\UrlRewriteFactory
     */
    protected $_urlRewriteFactory;


    /**
     * @param \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory
     * @param Context $context
     */
    public function __construct(
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        Context $context
    ) {
        $this->_urlRewriteFactory = $urlRewriteFactory;
        parent::__construct($context);
    }

    /**
     * @param $url
     * @param $id
     * @return void
     * @throws \Exception
     */
    public function saveUrl($url, $id)
    {
        $targetPathUrl = "notification/index/view/id/" . $id;

        $this->checkDuplicateUrl($targetPathUrl, $url);

        $urlRewriteModel = $this->_urlRewriteFactory->create();
        /* set current store id */
        $urlRewriteModel->setStoreId(1);
        /* this url is not created by system so set as 0 */
        $urlRewriteModel->setIsSystem(0);
        /* set actual url path to target path field */
        $urlRewriteModel->setTargetPath($targetPathUrl);
        /* set requested path which you want to create */
        $urlRewriteModel->setRequestPath($url);
        /* set current store id */
        $urlRewriteModel->save();
    }

    /**
     * @param $targetPathUrl
     * @param $url
     * @return void
     */
    public function checkDuplicateUrl($targetPathUrl)
    {

        $UrlRewriteCollection = $this->_urlRewriteFactory->create()->getCollection()
            ->addFieldToFilter('target_path', $targetPathUrl);
        $deleteItem = $UrlRewriteCollection->getFirstItem();
        if ($UrlRewriteCollection->getFirstItem()->getId()) {
            // target path does exist
            $deleteItem->delete();
        }

    }
}
