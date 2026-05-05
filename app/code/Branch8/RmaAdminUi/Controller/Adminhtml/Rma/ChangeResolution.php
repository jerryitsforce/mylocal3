<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Rma;

use Branch8\Rma\Model\Actions\SwitchResolution;
use Branch8\Rma\Model\Rma\Resolution;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Branch8\RmaAdminUi\Helper\Logger as CustomLogger;

class ChangeResolution extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    private \Branch8\Rma\Helper\Data $mpRmaHelper;

    private CustomLogger $logger;
    /**
     * @var SwitchResolution
     */
    private SwitchResolution $switchResolution;

    private \Webkul\MpRmaSystem\Model\DetailsFactory $detailFactory;

    /**
     * @param Context $context
     * @param \Branch8\Rma\Helper\Data $mpRmaHelper
     * @param CustomLogger $logger
     * @param \Webkul\MpRmaSystem\Model\DetailsFactory $detailsFactory
     * @param SwitchResolution $switchResolution
     */
    public function __construct(
        Context                                  $context,
        \Branch8\Rma\Helper\Data                 $mpRmaHelper,
        CustomLogger                             $logger,
        \Webkul\MpRmaSystem\Model\DetailsFactory $detailsFactory,
        SwitchResolution                         $switchResolution
    )
    {
        parent::__construct($context);
        $this->mpRmaHelper = $mpRmaHelper;
        $this->logger = $logger;
        $this->detailFactory = $detailsFactory;
        $this->switchResolution = $switchResolution;
    }

    /**
     * Change Rma Action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->resultRedirectFactory
                ->create()
                ->setPath('mprmasystem/rma/index');
        }
        $data = $this->getRequest()->getParams();
        $rmaId = $this->mpRmaHelper->decrypt($data['rma_id']);
        $newResolution = (int)$this->getRequest()->getParam('resolution');
        $rmaDetail = $this->detailFactory->create()->load($rmaId);
        $valid = [
            Resolution::RESOLUTION_TYPE_EXCHANGE,
            Resolution::RESOLUTION_TYPE_RETURN_REFUND
        ];
        if (!is_numeric($rmaId) || !$rmaDetail || !$rmaDetail->getId()) {
            $this->messageManager->addError(__("Invalid rma Id."));
            return $this->resultRedirectFactory
                ->create()
                ->setPath(
                    'mprmasystem/rma/index'
                );
        }
        $isCustomer = $this->mpRmaHelper->getCustmerByRmaId($rmaId);
        if (!$isCustomer) {
            $this->messageManager->addError(__("Customer not exists"));
            return $this->resultRedirectFactory
                ->create()
                ->setPath(
                    'mprmasystem/rma/edit',
                    ['id' => $rmaId, 'back' => null, '_current' => true]
                );
        }
        if (!in_array($newResolution, $valid)) {
            $this->messageManager->addError(__("Invalid resolution"));
            return $this->resultRedirectFactory
                ->create()
                ->setPath(
                    'mprmasystem/rma/edit',
                    ['id' => $rmaId, 'back' => null, '_current' => true]
                );
        }

        if($rmaDetail->getResolutionType() == $this->mpRmaHelper::RESOLUTION_CANCEL) {
            $this->messageManager->addError(__("Already in Cancellation Process."));
            return $this->resultRedirectFactory
                ->create()
                ->setPath(
                    'mprmasystem/seller/rma',
                    ['id' => $rmaId, 'back' => null, '_current' => true]
                );
        }
        try {
            $status = $this->switchResolution->execute($rmaDetail, (int)$newResolution);
            $label = $newResolution === Resolution::RESOLUTION_TYPE_RETURN_REFUND ? __('Return') : __('Replace');
            if (!$status) {
                throw new LocalizedException(__('Can not change resolution'));
            }
            $this->messageManager->addSuccessMessage(__('Change resolution "%1" successfully', $label));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $exception) {
            $this->logger->info($exception->getTraceAsString());
            $this->messageManager->addErrorMessage(__('Unknown Error'));
        }

        return $this->resultRedirectFactory
            ->create()
            ->setPath(
                'mprmasystem/rma/edit',
                ['id' => $rmaId, 'back' => null, '_current' => true]
            );
    }
}
