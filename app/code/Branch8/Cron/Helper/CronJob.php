<?php
namespace Branch8\Cron\Helper;

use Branch8\Cron\Model\CronManualExecutionFactory;
use Magento\Backend\Model\Auth\Session;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Mageplaza\CronSchedule\Helper\Data;
use Mageplaza\CronSchedule\Model\JobFactory;
use \Magento\Framework\Message\ManagerInterface;

class CronJob
{

    /** @var \Magento\Cron\Model\ScheduleFactory $scheduleFactory */
    protected $scheduleFactory;

    /** @var \Mageplaza\CronSchedule\Model\JobFactory $jobFactory */
    protected $jobFactory;

    /** @var \Mageplaza\CronSchedule\Helper\Data $helper */
    protected $helper;

    /** @var \Magento\Framework\Message\ManagerInterface $messageManager */
    protected $messageManager;

    /** @var \Branch8\Cron\Model\CronManualExecutionFactory $cronManualExecutionFactory */
    protected $cronManualExecutionFactory;

    protected $session;

    public function __construct(
        Data $helper,
        JobFactory $jobFactory,
        ScheduleFactory $scheduleFactory,
        ManagerInterface $messageManager,
        CronManualExecutionFactory $cronManualExecutionFactory,
        Session $session
    ) {
        $this->helper                     = $helper;
        $this->jobFactory                 = $jobFactory;
        $this->scheduleFactory            = $scheduleFactory;
        $this->messageManager             = $messageManager;
        $this->cronManualExecutionFactory = $cronManualExecutionFactory;
        $this->session                    = $session;
    }

    public function executeJob($jobData, &$result, $showError = false)
    {

        if (isset($jobData['status']) && empty($jobData['status'])) {
            return;
        }

        $success = &$result['success'];
        $failure = &$result['failure'];

        $data = [
            'job_code'     => $jobData['name'],
            'status'       => Schedule::STATUS_SUCCESS,
            'created_at'   => $this->helper->getTime(),
            'scheduled_at' => $this->helper->getTime(true),
            'executor'     => $this->session->getUser()->getUsername(),
        ];

        $schedule     = $this->scheduleFactory->create()->setData($data);
        $manualRecord = $this->cronManualExecutionFactory->create()->setData($data);

        try {
            $this->jobFactory->create()->setData($jobData)->executeJob($schedule);
            $manualRecord->setExecutedAt($schedule->getExecutedAt());
            $manualRecord->setFinishedAt($schedule->getFinishedAt());

            $success++;
        } catch (\Exception $e) {
            $failure++;

            $errMsg = [
                'status'      => Schedule::STATUS_ERROR,
                'messages'    => $e->getMessage(),
                'executed_at' => null,
            ];

            $schedule->addData($errMsg);
            $manualRecord->addData($errMsg);

            if ($showError) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        try {
            $schedule->save();
            $manualRecord->save();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

    }
}
