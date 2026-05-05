<?php
namespace Branch8\Cms\Block;

use Magento\Framework\App\State;

class Block extends \Magento\Cms\Block\Block
{
    protected $appState;

    protected $mobileDetect;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\BlockFactory $blockFactory,
        State $appState,
        \Branch8\HotaiCore\Model\Detection\MobileDetect $mobileDetect,
        array $data = []
    ) {
        parent::__construct($context, $filterProvider, $storeManager, $blockFactory, $data);
        $this->appState = $appState;
        $this->mobileDetect = $mobileDetect;
    }

    protected function _toHtml()
    {
        $blockId = $this->getBlockId();
        $html = '';
        if ($blockId) {
            $storeId = $this->_storeManager->getStore()->getId();
            /** @var \Magento\Cms\Model\Block $block */
            $block = $this->_blockFactory->create();
            $block->setStoreId($storeId)->load($blockId);
            $areaCode = $this->appState->getAreaCode();
            if (
                $block->isActive() && 
                (
                    $areaCode === \Magento\Framework\App\Area::AREA_ADMINHTML 
                    || $block->getVisibility() == \Branch8\Cms\Model\Config\Source\CmsAgent::TYPE_UNRETRICTED
                    || (
                        $areaCode === \Magento\Framework\App\Area::AREA_FRONTEND && (
                            ($this->mobileDetect->isHotaiApp() && $block->getVisibility() == \Branch8\Cms\Model\Config\Source\CmsAgent::TYPE_APP)
                            || (!$this->mobileDetect->isHotaiApp() && $block->getVisibility() == \Branch8\Cms\Model\Config\Source\CmsAgent::TYPE_WEB)
                        ) 
                    )
                )
            ) {
                $html = $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent());
            }
        }
        return $html;
    }
}
