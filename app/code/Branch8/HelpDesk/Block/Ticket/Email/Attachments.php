<?php

namespace Branch8\HelpDesk\Block\Ticket\Email;

use Branch8\HelpDesk\Model\ResourceModel\Attachment;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

class Attachments extends \Magento\Framework\View\Element\Template
{
    /**
     * @var OrderRepositoryInterface
     */
    private $attachmentCollectionFactory;

    /**
     * @param Context $context
     * @param array $data
     * @param Attachment\CollectionFactory $attachmentCollectionFactory
     */
    public function __construct(
        Context                      $context,
        Attachment\CollectionFactory $attachmentCollectionFactory,
        array                        $data = [],
    )
    {
        $this->attachmentCollectionFactory = $attachmentCollectionFactory ?: ObjectManager::getInstance()->get(
            \Branch8\HelpDesk\Model\ResourceModel\Attachment\CollectionFactory::class);
        parent::__construct($context, $data);
    }

    /**
     * @return array|mixed|null
     */
    public function getAttachments()
    {
        $ids = $this->getData('attachment_ids');
        if ($ids === null) {
            return [];
        }
        $attachmentIds = explode(',', $ids);
        if ($attachmentIds) {
            $collection = $this->attachmentCollectionFactory->create()->addFieldToFilter('attachment_id', ['in' => $attachmentIds]);
            $this->setData('attachments', $collection);
        }
        return $this->getData('attachments');
    }
}
