<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Services;

use Branch8\HelpDesk\Model\ResourceModel\Attachment;
use Magento\Framework\View\LayoutFactory;

class GetAttachmentsAsHtml
{
    private $blockFactory;

    /**
     * @param LayoutFactory $factory
     */
    public function __construct(
        \Magento\Framework\View\Element\BlockFactory $blockFactory
    )
    {
        $this->blockFactory = $blockFactory;
    }

    /**
     * @param Attachment\Collection $attachments
     * @return string
     */
    public function get(Attachment\Collection $attachments)
    {
        $html = [];
        /**
         * @var $item \Branch8\HelpDesk\Model\Attachment
         */
        foreach ($attachments as $item) {
            $html[] = $this->blockFactory->createBlock(
                \Magento\Framework\View\Element\Template::class
            )->setAttachment($item)->setTemplate('Branch8_HelpDesk::attachment.phtml')->toHtml();
        }
        return join('', $html);
    }
}
