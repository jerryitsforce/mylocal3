<?php
namespace Branch8\Cms\Block\Widget;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Cms\Model\Block as CmsBlock;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\App\State;

class Block extends \Magento\Cms\Block\Widget\Block
{
    protected $appState;

    protected $mobileDetect;
    /**
     * @var CmsBlock
     */
    private $block;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Magento\Cms\Model\BlockFactory $blockFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Cms\Model\BlockFactory $blockFactory,
        State $appState,
        \Branch8\HotaiCore\Model\Detection\MobileDetect $mobileDetect,
        array $data = []
    ) {
        parent::__construct($context, $filterProvider, $blockFactory, $data);
        $this->appState = $appState;
        $this->mobileDetect = $mobileDetect;
    }

    /**
     * Prepare block text and determine whether block output enabled or not.
     *
     * Prevent blocks recursion if needed.
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
        $blockId = $this->getData('block_id');
        $blockHash = get_class($this) . $blockId;

        if (isset(self::$_widgetUsageMap[$blockHash])) {
            return $this;
        }
        self::$_widgetUsageMap[$blockHash] = true;

        $block = $this->getBlock();
        
        $areaCode = $this->appState->getAreaCode();
        if ($block && $block->isActive() && 
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
            
            try {
                $storeId = $this->getData('store_id') ?? $this->_storeManager->getStore()->getId();
                $this->setText(
                    $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent())
                );
            } catch (NoSuchEntityException $e) {
                $this->setText('');
            }
        }else{
            $this->setText('');
        }
        unset(self::$_widgetUsageMap[$blockHash]);
        return $this;
    }

    /**
     * Get block
     *
     * @return CmsBlock|null
     */
    private function getBlock(): ?CmsBlock
    {
        if ($this->block) {
            return $this->block;
        }

        $blockId = $this->getData('block_id');

        if ($blockId) {
            try {
                $storeId = $this->_storeManager->getStore()->getId();
                /** @var \Magento\Cms\Model\Block $block */
                $block = $this->_blockFactory->create();
                $block->setStoreId($storeId)->load($blockId);
                $this->block = $block;

                return $block;
            } catch (NoSuchEntityException $e) {
            }
        }

        return null;
    }
}
