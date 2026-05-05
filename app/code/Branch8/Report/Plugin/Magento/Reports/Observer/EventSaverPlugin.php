<?php
declare(strict_types=1);

namespace Branch8\Report\Plugin\Magento\Reports\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Reports Event observer model
 */
class EventSaverPlugin
{
    const XML_PATH = 'reports/options/use_queue_to_track';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reports\Model\EventFactory $event
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Visitor $customerVisitor
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        readonly ScopeConfigInterface                               $scopeConfig,
        readonly \Magento\Store\Model\StoreManagerInterface         $storeManager,
        readonly \Magento\Reports\Model\EventFactory                $event,
        readonly \Magento\Customer\Model\Session                    $customerSession,
        readonly \Magento\Customer\Model\Visitor                    $customerVisitor,
        readonly \Magento\Framework\MessageQueue\PublisherInterface $publisher
    )
    {

    }

    /***
     * @param $subject
     * @param $process
     * @param $eventTypeId
     * @param $objectId
     * @param $subjectId
     * @param $subtype
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundSave($subject, $process, $eventTypeId, $objectId, $subjectId = null, $subtype = 0)
    {
        $enabled = (bool)$this->scopeConfig->getValue(self::XML_PATH);
        if (!$enabled) {
            return $process($eventTypeId, $objectId, $subjectId, $subtype);
        }
        if ($subjectId === null) {
            if ($this->customerSession->isLoggedIn()) {
                $subjectId = $this->customerSession->getCustomerId();
            } else {
                $subjectId = $this->customerVisitor->getId();
                $subtype = 1;
            }
        }
        $storeId = $this->storeManager->getStore()->getId();
        $data = json_encode([
            'event_type_id' => $eventTypeId,
            'object_id' => $objectId,
            'subject_id' => $subjectId,
            'subtype' => $subtype,
            'store_id' => $storeId,
        ]);
        $this->publisher->publish('event.report.tracking', $data);
    }
}
