<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\CustomNotification\Controller\Adminhtml\Ajax;

use Branch8\CustomNotification\Exception\OneIdImportValidateException;
use Branch8\CustomNotification\Model\ConfigData;
use Branch8\CustomNotification\Model\OneIdImportHistoryFactory;
use Branch8\CustomNotification\Model\OneIdListImportHandler;
use Branch8\CustomNotification\Model\ResourceModel\OneIdImportHistory;
use Magenest\NotificationBox\Model\NotificationFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;

/**
 * Product validate
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Validate extends \Magento\Backend\App\Action implements HttpPostActionInterface, HttpGetActionInterface
{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    private ConfigData $configData;

    private $notificationFactory;

    private OneIdListImportHandler $oneIdListImportHandler;

    private OneIdImportHistoryFactory $oneIdImportHistoryFactory;
    private PostProcess $postProcess;

    /**
     * @param Action\Context $context
     * @param ConfigData $configData
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param NotificationFactory $notification
     * @param OneIdListImportHandler $oneIdListImportHandler
     * @param OneIdImportHistoryFactory $oneIdImportHistoryFactory
     * @param PostProcess $postProcess
     */
    public function __construct(
        \Magento\Backend\App\Action\Context              $context,
        ConfigData                                       $configData,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        NotificationFactory                              $notification,
        OneIdListImportHandler                           $oneIdListImportHandler,
        OneIdImportHistoryFactory                        $oneIdImportHistoryFactory,
        PostProcess                                      $postProcess
    )
    {
        parent::__construct($context);
        $this->configData = $configData;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->notificationFactory = $notification;
        $this->oneIdListImportHandler = $oneIdListImportHandler;
        $this->oneIdImportHistoryFactory = $oneIdImportHistoryFactory;
        $this->postProcess = $postProcess;
    }

    /**
     * Validate product
     *
     * @return \Magento\Framework\Controller\Result\Json
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $response->setError(false);
        try {
            $data = $this->getRequest()->getPostValue();
            $oneIdLevel = $this->configData->getOneIdGroup();
            $hasOneIDGroup = in_array($oneIdLevel, $this->getRequest()->getParam('customer_levels', []));
            $data = $this->postProcess->execute($data);
            $notification = $this->notificationFactory->create()->load($this->getRequest()->getParam('id'));
            $notification->setData($data);
            $importHistory = $this->oneIdImportHistoryFactory->create()->load(
                $notification->getData('oneid_import_history')
            );
            if ($hasOneIDGroup) {
                if ((!$notification->getId() && empty($importHistory->getId()))
                    || ($notification->getId() && empty($importHistory->getId()))
                ) {
                    $this->setOneIdlistError($response,
                        'MISSING_ONEID_LIST',
                        [__('Please upload oneID file')]
                    );
                }
                if (($importHistory && $importHistory->getId() &&
                    empty($importHistory->getData('oneid_list')))

                ) {
                    $this->setOneIdlistError($response,
                        'ERROR_VERIFY_ONEIDLIST',
                        [__('No oneID list available')]
                    );
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response->setError(true);
            $response->setMessages([$e->getMessage()]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $response->setError(true);
            $response->setMessages([$e->getMessage()]);
        }
        return $this->resultJsonFactory->create()->setData($response);
    }

    /**
     * @return void
     */
    private function setOneIdlistError($response, $code, $messages)
    {
        $response->setError(true);
        $response->setCode($code);
        $response->setMessages($messages);
    }
}
