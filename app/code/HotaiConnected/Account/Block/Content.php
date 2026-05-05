<?php
namespace HotaiConnected\Account\Block;

use Magento\Framework\View\Element\Template;
use Magento\Cms\Model\BlockFactory;

class Content extends Template
{
    protected $blockFactory;
    
    public function __construct(
        Template\Context $context,
        BlockFactory $blockFactory,
        array $data = []
    ) {
        $this->blockFactory = $blockFactory;
        parent::__construct($context, $data);
    }

    public function getCmsBlockHtml($identifier)
    {
        $block = $this->blockFactory->create()
            ->load($identifier, 'identifier');
            
        if ($block->getId()) {
            return $block->getContent();
        }
        return '';
    }
}
