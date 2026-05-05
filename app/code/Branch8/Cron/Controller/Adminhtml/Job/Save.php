<?php
namespace Branch8\Cron\Controller\Adminhtml\Job;

use Magento\Framework\App\ResponseInterface;
use Mageplaza\CronSchedule\Controller\Adminhtml\AbstractJob;
use Branch8\Cron\Helper\CronJob;

class Save extends AbstractJob
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

    public function aroundExecute(
        \Mageplaza\CronSchedule\Controller\Adminhtml\Job\Save $subject,
        callable $proceed
    ){
         /** @var Http $request */
        $request = $subject->getRequest();

        if ($data = $request->getPostValue()) {
            $object = $subject->_initJob('org_name');
            $newObj = clone $object;

            $data['name'] = $request->getParam('code');

            try {
                $object->saveJob($newObj->addData($data));
                $subject->cacheTypeList->cleanType('config');

                if ($request->getParam('is_execute')) {
                    $result = ['success' => 0, 'failure' => 0];

                    $this->cronJob->executeJob($data, $result, true);

                    if ($result['success']) {
                        $subject->messageManager->addSuccessMessage(__('The cron job has been saved and executed.'));
                    }
                } else {
                    $subject->messageManager->addSuccessMessage(__('The cron job has been saved.'));
                }

                $subject->_session->setMpCronScheduleData(false);

                if ($request->getParam('back', false)) {
                    return $subject->_redirect('*/*/edit', ['name' => $data['name'], '_current' => true]);
                }
            } catch (\Exception $e) {
                $subject->messageManager->addErrorMessage($e->getMessage());
                $subject->_session->setMpCronScheduleData($data);

                if ($name = $request->getParam('name')) {
                    return $subject->_redirect('*/*/edit', ['name' => $name, '_current' => true]);
                }
            }
        }

        return $subject->_redirect('*/*/');
    }
}