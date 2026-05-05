<?php
declare(strict_types=1);

namespace Branch8\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class RegisterCronFlag implements ObserverInterface
{

    protected $statusChangeLogHelper;

    protected $registry;

    public function __construct(
        \Branch8\Sales\Helper\StatusChangeLog $statusChangeLogHelper,
        \Magento\Framework\Registry $registry
    )
    {
        $this->statusChangeLogHelper = $statusChangeLogHelper;
        $this->registry = $registry;
    }

    /**
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {

        try {
            $jobName = $observer->getEvent()->getData('job_name');
            $explJobName = explode('/', (string)$jobName);
            if($this->registry->registry('current_cron_name')){
                $this->registry->unregister('current_cron_name');
            }
            $this->registry->register('current_cron_name', isset($explJobName[2]) ? $explJobName[2] : 'Undefined Cron');
        } catch (\Exception $exception) {
            
        }
    }
}
