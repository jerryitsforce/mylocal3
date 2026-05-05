<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Plugin\Magento\Sales\Controller\Adminhtml\Order\Create;

use Magento\Framework\App\ObjectManager;

class SavePlugin
{
    /**
     * @param $subject
     * @param callable $process
     * @param ...$args
     * @return \Magento\Framework\Controller\Result\Redirect|mixed
     */
    public function aroundExecute($subject, callable $process, ...$args)
    {
        $isEditingParentOrder = !empty($this->getCoreSession()->hasOldParentOrderId());
        $result = $process();
        if ($isEditingParentOrder && $result instanceof \Magento\Framework\Controller\Result\Redirect) {
            $this->getCoreSession()->unsOldParentOrderId();
            $result->setPath('sales/parent_order/index');
        }
        return $result;
    }

    /**
     * @return \Magento\Framework\Session\SessionManager|mixed
     */
    private function getCoreSession()
    {
        return ObjectManager::getInstance()->get(\Magento\Framework\Session\SessionManager::class);
    }
}
