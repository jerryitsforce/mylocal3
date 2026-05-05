<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Observer;

use Exception;
use Magento\Customer\Controller\Account\CreatePost;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Branch8\Blog\Helper\Data;
use Branch8\Blog\Model\AuthorFactory;

/**
 * Class CreateAuthor
 * @package Branch8\Blog\Observer
 */
class CreateAuthor implements ObserverInterface
{

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var AuthorFactory
     */
    protected $author;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param Data $helper
     * @param AuthorFactory $authorFactory
     * @param ManagerInterface $manager
     */
    public function __construct(
        Data $helper,
        AuthorFactory $authorFactory,
        ManagerInterface $manager
    ) {
        $this->author         = $authorFactory;
        $this->_helper        = $helper;
        $this->messageManager = $manager;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $accountController = $observer->getData('account_controller');
        $customer          = $observer->getData('customer');

        /** @var CreatePost $accountController */
        if ($this->_helper->isEnabled() && $accountController->getRequest()->getParam('is_mp_author')) {
            $data   = [
                'customer_id' => $customer->getId(),
                'name'        => $customer->getFirstname(),
                'type'        => '1',
                'status'      => $this->_helper->getConfigGeneral('auto_approve') ? 1 : 0
            ];
            $author = $this->author->create();
            $author->addData($data);
            try {
                $author->save();
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Author.'));
            }
        }
    }
}
