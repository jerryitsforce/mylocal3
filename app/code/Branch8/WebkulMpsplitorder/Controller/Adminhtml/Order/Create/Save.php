<?php
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Controller\Adminhtml\Order\Create;

use Branch8\WebkulMpsplitorder\Exception\CannotSubmitOrderException;
use Branch8\WebkulMpsplitorder\Model\VerifyQuote\VerifyQuoteComposite;
use Branch8\WebkulMpsplitorder\Model\VerifyQuote\VerifyResult;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Registry;

class Save extends \Magento\Sales\Controller\Adminhtml\Order\Create implements HttpPostActionInterface
{

    private $path;

    private $pathParams = [];

    private $registry;

    private $verifyQuote;

    /**
     * @return Registry
     */
    private function getRegistry()
    {
        if ($this->registry === null) {
            $this->registry = ObjectManager::getInstance()->get(Registry::class);
        }
        return $this->registry;
    }

    /**
     * @return VerifyQuoteComposite|mixed
     */
    private function getVerifyComposite()
    {
        if ($this->verifyQuote === null) {
            $this->verifyQuote = ObjectManager::getInstance()->get(VerifyQuoteComposite::class);
        }
        return $this->verifyQuote;
    }

    /**
     * Saving quote and create order
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->path = 'sales/*/';
        $this->pathParams = [];
        try {
            // check if the creation of a new customer is allowed
            if (!$this->_authorization->isAllowed('Magento_Customer::manage')
                && !$this->_getSession()->getCustomerId()
                && !$this->_getSession()->getQuote()->getCustomerIsGuest()
            ) {
                return $this->resultForwardFactory->create()->forward('denied');
            }
            $this->_getOrderCreateModel()->getQuote()->setCustomerId($this->_getSession()->getCustomerId());
            $this->_processActionData('save');
            $paymentData = $this->getRequest()->getPost('payment');
            if ($paymentData) {
                $paymentData['checks'] = [
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_INTERNAL,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
                ];
                $this->_getOrderCreateModel()->setPaymentData($paymentData);
                $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentData);
            }
            /**
             * @var \Branch8\WebkulMpsplitorder\Model\AdminOrder\Create
             */
            $orderCreateModel = $this->_getOrderCreateModel();
            $post = $this->getRequest()->getPost('order');
            $orderCreateModel->setIsValidate(true)
                ->importPostData($post);
            // verify quote before submit
            $result = $this->verifyQuote($this->_getSession());
            if (!$result->getResult()) {
                $redirect= $this->handleVerifyError($result);
                $this->_getSession()->clearStorage();
                return $redirect;
            }
            $order = $orderCreateModel->createOrder();
            $this->messageManager->addSuccessMessage(__('You created the order.'));
            list($path, $pathParams) = $this->resolveRedirectPathAndParams(
                $order
            );
            $this->path = $path;
            $this->pathParams = $pathParams;
            $this->_getSession()->clearStorage();
        } catch (PaymentException $e) {
            $this->_getOrderCreateModel()->saveQuote();
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->messageManager->addErrorMessage($message);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // customer can be created before place order flow is completed and should be stored in current session
            $this->_getSession()->setCustomerId((int)$this->_getSession()->getQuote()->getCustomerId());
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->messageManager->addErrorMessage($message);
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Order saving error: %1', $e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath($this->path, $this->pathParams);
    }

    /**
     * @param VerifyResult $result
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function handleVerifyError(VerifyResult $result)
    {
        $this->path = 'sales/*/';
        $this->pathParams = [];
        $error = $result->getError() ? $result->getError() : __('Unknown error')->render();
        $this->messageManager->addErrorMessage($error);
        $currentAction = (string)$this->_getSession()->getCurrentAction();
        switch ($currentAction) {
            case 'reorder_parent_order':
                $previousOrderId = $this->_getSession()->getReOrderParentOrderFrom();
                if ($this->_authorization->isAllowed('Branch8_MarketPlaceParentOrderAdminUi::edit')
                    && $previousOrderId
                ) {
                    $this->pathParams = ['id' => $previousOrderId];
                    $this->path = 'sales/parent_order/view';
                } else {
                    $this->path = 'sales/parent_order/index';
                }
            case 'edit_parent_order':
                $previousOrderId = $this->_getSession()->getEditParentOrderFrom();
                if ($this->_authorization->isAllowed('Branch8_MarketPlaceParentOrderAdminUi::edit')
                    && $previousOrderId
                ) {
                    $this->pathParams = ['id' => $previousOrderId];
                    $this->path = 'sales/parent_order/view';
                } else {
                    $this->path = 'sales/parent_order/index';
                }
                break;
        }
        return $this->resultRedirectFactory->create()->setPath(
            $this->path, $this->pathParams
        );
    }

    /**
     * @param \Magento\Backend\Model\Session\Quote $session
     * @return \Branch8\WebkulMpsplitorder\Model\VerifyQuote\VerifyResult
     */
    public function verifyQuote(\Magento\Backend\Model\Session\Quote $session)
    {
        $currentAction = (string)$this->_getSession()->getCurrentAction();
        return $this->getVerifyComposite()->verify(
            $currentAction,
            $this->_getSession(),
            $this->_getOrderCreateModel()
        );
    }

    /**
     * @param $order
     * @return array
     */
    private function resolveRedirectPathAndParams($order)
    {
        $currentAction = $this->_getSession()->getCurrentAction();
        $parentOrderId = $this->getRegistry()->registry('new_parent_order_id');
        $pathParams = [];
        switch ($currentAction) {
            case 'reorder_parent_order':
            case 'edit_parent_order':
                if ($this->_authorization->isAllowed('Branch8_MarketPlaceParentOrderAdminUi::edit')
                    && $parentOrderId
                ) {
                    $pathParams = ['id' => $parentOrderId];
                    $path = 'sales/parent_order/view';
                } else {
                    $path = 'sales/parent_order/index';
                }
                if ($currentAction === 'edit_parent_order'
                    && ($oldParentOrder = $this->_getSession()->getEditParentOrderFrom())
                    && $parentOrderId
                ) {
                    $this->_eventManager->dispatch(
                        'adminhtml_after_edit_old_parent_order',
                        [
                            'old_parent_order_id' => $oldParentOrder,
                            'new_parent' => $parentOrderId
                        ]
                    );
                }
                break;
            default:
                // default native order;
                if ($this->_authorization->isAllowed('Magento_Sales::actions_view')) {
                    $pathParams = ['order_id' => $order->getId()];
                    $path = 'sales/order/view';
                } else {
                    $path = 'sales/order/index';
                }
        }
        return [
            $path,
            $pathParams
        ];
    }
}
