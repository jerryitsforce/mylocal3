<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController;

use Branch8\MarketPlaceParentOrderFrontendUi\Model\ReorderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

/**
 * Abstract class for controllers Reorder(Customer) and Reorder(Guest)
 */
abstract class Reorder extends Action\Action implements HttpPostActionInterface,Action\HttpGetActionInterface
{
    /**
     * @var OrderLoaderInterface
     */
    protected $orderLoader;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var ReorderInterface
     */
    private $reorder;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param Action\Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param Registry $registry
     * @param ReorderInterface|null $reorder
     * @param CheckoutSession|null $checkoutSession
     */
    public function __construct(
        Action\Context       $context,
        OrderLoaderInterface $orderLoader,
        Registry             $registry,
        ReorderInterface     $reorder = null,
        CheckoutSession      $checkoutSession = null
    )
    {
        $this->orderLoader = $orderLoader;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
        $this->reorder = $reorder ?: ObjectManager::getInstance()->get(
            ReorderInterface::class);
        $this->checkoutSession = $checkoutSession ?: ObjectManager::getInstance()->get(CheckoutSession::class);
    }

    /**
     * Action for reorder
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->orderLoader->load($this->_request);
        if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
            return $result;
        }
        $order = $this->_coreRegistry->registry('current_parent_order');
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $reorderOutput = $this->reorder->execute($order);
        } catch (LocalizedException $localizedException) {
            $this->messageManager->addErrorMessage($localizedException->getMessage());
            return $resultRedirect->setPath('checkout/cart');
        }

        // Set quote id for guest session: \Magento\Quote\Api\CartRepositoryInterface::save doesn't set quote id
        // to session for guest customer, as it does \Magento\Checkout\Model\Cart::save which is deprecated.
        $this->checkoutSession->setQuoteId($reorderOutput->getCart()->getId());

        $errors = $reorderOutput->getErrors();
        if (!empty($errors)) {
            $useNotice = $this->_objectManager->get(\Magento\Checkout\Model\Session::class)->getUseNotice(true);
            foreach ($errors as $error) {
                $useNotice
                    ? $this->messageManager->addNoticeMessage($error->getMessage())
                    : $this->messageManager->addErrorMessage($error->getMessage());
            }
        }
        return $resultRedirect->setPath('checkout/cart');
    }
}
