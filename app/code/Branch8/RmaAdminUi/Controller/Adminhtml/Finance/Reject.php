<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Finance;

use Branch8\Rma\Model\Actions\ApproveFinance;
use Branch8\Rma\Model\Actions\RejectFinance;
use Branch8\RmaAdminUi\Model\Permission;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Webkul\MpRmaSystem\Model\DetailsRepository;
use Webkul\MpRmaSystem\Model\ConversationFactory;

/**
 * Approve Finance Controller
 */
class Reject extends Action
{
    /**
     * Using for Rma admin resource
     */
    public const ADMIN_RESOURCE = Permission::FINAL_APPROVE;
    private DetailsRepository $detailRepository;
    private RejectFinance $rejectFinance;
    private ConversationFactory $conversationFactory;
    private $date;

    /**
     * @param Context $context
     * @param DetailsRepository $detailsRepository
     * @param RejectFinance $rejectFinance
     * @param ConversationFactory $conversationFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        Context                                     $context,
        DetailsRepository                           $detailsRepository,
        RejectFinance                               $rejectFinance,
        ConversationFactory                         $conversationFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    )
    {
        parent::__construct($context);
        $this->rejectFinance = $rejectFinance;
        $this->date = $date;
        $this->detailRepository = $detailsRepository;
        $this->conversationFactory = $conversationFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create()->setPath(
            'mprmasystem/rma/index',
            ['_secure' => $this->getRequest()->isSecure()]
        );
        try {
            $id = $this->getRequest()->getParam('id');
            $rmaDetail = $this->detailRepository->getById($id);
            $message = __('%1: Approve Reject',
                $this->_auth->getUser()->getName());
            $this->rejectFinance->execute($rmaDetail);
            $this->conversationFactory->create()->setRmaId(
                $id
            )->setCreatedTime(
                $this->date->gmtDate('Y-m-d H:i:s')
            )->setMessage(
                $message
            )->setSenderType(0)
                ->save();// 0 is admin , guess so
            $this->messageManager->addSuccessMessage(
                __('Reject Finance successfully', $id)
            );
            return $redirect;
        } catch (NoSuchEntityException $exception) {
            $this->messageManager->addErrorMessage(
                __('Rma Details not exist')
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage(
                __($e->getMessage()));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('The requested RMA no longer exist.'));
        }
        return $redirect;
    }
}
