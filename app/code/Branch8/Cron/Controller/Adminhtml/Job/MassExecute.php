<?php
namespace Branch8\Cron\Controller\Adminhtml\Job;

use Magento\Framework\App\ResponseInterface;
use Mageplaza\CronSchedule\Controller\Adminhtml\AbstractJob;
use Branch8\Cron\Helper\CronJob;

class MassExecute extends AbstractJob
{

    /** 
     * @var \Branch8\Cron\Helper\CronJob $cronJob
     */
    public $cronJob;

    public function __construct(
        CronJob $cronJob
    ) {
        $this->cronJob = $cronJob;
    }

    public function execute() {

    }

    /**
     * @return ResponseInterface
     */
    public function aroundExecute(
        \Mageplaza\CronSchedule\Controller\Adminhtml\Job\MassExecute $subject,
        callable $proceed
    )
    {
        $data = $subject->getRequest()->getParams();

        $result = ['success' => 0, 'failure' => 0];

        foreach ($subject->getSelectedRecords($data) as $name) {
            $this->cronJob->executeJob($subject->helper->getJobs($name), $result);
        }

        if ($success = $result['success']) {
            $subject->cacheTypeList->cleanType('config');
            $subject->messageManager->addSuccessMessage(__('A total of %1 record(s) have been executed.', $success));
        }

        if ($failure = $result['failure']) {
            $subject->messageManager->addErrorMessage(__(
                'A total of %1 record(s) can not execute. Please check the Cron Jobs Logs for more details.',
                $failure
            ));
        }

        return $subject->_redirect('*/*/');
    }
}
