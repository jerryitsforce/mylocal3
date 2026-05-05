<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Branch8\HelpDesk\Api\Data\GuardTicketInterface;
use Branch8\HelpDesk\Api\Data\TicketInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Exception\LocalizedException;
use Branch8\HelpDesk\Model\Ticket\CodeFormatter;
use Magento\User\Model\UserFactory;
use Branch8\HelpDesk\Api\Data\TicketExtensionInterface;

/**
 * Ticket HelpDesk
 * @method Ticket setCreateBy($value)
 * @method int getCreateBy()
 */
class Ticket extends \Magento\Framework\Model\AbstractExtensibleModel implements GuardTicketInterface, TicketInterface
{
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;
    /**
     * @var TeamFactory
     */
    private $teamFactory;
    /**
     * @var
     */
    private $userFactory;
    /**
     * @var
     */
    private $customerfactory;
    /**
     * @var
     */
    private $customer = null;
    /**
     * @var
     */
    private $messageCollectionFactory;
    /**
     * @var
     */
    private $messages;
    /**
     * @var
     */
    private $category = null;
    /**
     * @var null
     */
    private $team = null;
    /**
     * @var null
     */
    private $user = null;

    /**
     * @param ResourceModel\Message\CollectionFactory $messageCollectionFactory
     * @param UserFactory $userFactory
     * @param TeamFactory $teamFactory
     * @param CategoryFactory $categoryFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        CustomerFactory                                                 $customerFactory,
        \Branch8\HelpDesk\Model\ResourceModel\Message\CollectionFactory $messageCollectionFactory,
        UserFactory                                                     $userFactory,
        TeamFactory                                                     $teamFactory,
        CategoryFactory                                                 $categoryFactory,
        \Magento\Framework\Model\Context                                $context,
        \Magento\Framework\Registry                                     $registry,
        ExtensionAttributesFactory                                      $extensionFactory,
        AttributeValueFactory                                           $customAttributeFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource         $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb                   $resourceCollection = null,
        array                                                           $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
        $this->categoryFactory = $categoryFactory;
        $this->teamFactory = $teamFactory;
        $this->userFactory = $userFactory;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->customerfactory = $customerFactory;
    }


    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Ticket::class);
    }

    /**
     * @param Customer $customer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function populateWithCustomerData(Customer $customer)
    {
        $this->setCustomerId($customer->getId())
            ->setCustomerEmail($customer->getEmail());
        if (!$this->getCustomerName()) {
            $this->setCustomerName($customer->getName());
        }
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setTitle($value)
    {
        $this->setData('title', $value);
        return $this;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function generateCode()
    {
        if (empty($this->getId())) {
            throw new LocalizedException(__('ID need to be required to generate code'));
        }
        $code = CodeFormatter::format($this->getId());
        $this->setCode($code);
        return $this;
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function saveCode()
    {
        $connection = $this->getResource()->getConnection();
        $data = [
            'code' => $this->getCode()
        ];
        $connection->update($this->getResource()->getMainTable(),
            $data, ['ticket_id = ?' => $this->getId()]
        );
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getCategoryId()
    {
        return $this->_getData(self::CATEGORY_ID);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setCategoryId($value)
    {
        $this->setData(self::CATEGORY_ID, $value);
        return $this;
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setCode($value)
    {
        $this->setData(self::CODE, $value);
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getCode()
    {
        return $this->_getData(self::CODE);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setCustomerId($value)
    {
        $this->setData(self::CUSTOMER_ID, $value);
        return $this;
    }

    /**
     * @return int|mixed|null
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setCustomerEmail($value)
    {
        $this->setData(self::CUSTOMER_EMAIL, $value);
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getCustomerEmail()
    {
        return $this->_getData(self::CUSTOMER_EMAIL);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setCustomerName($value)
    {
        $this->setData(self::CUSTOMER_NAME, $value);
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getCustomerName()
    {
        return $this->_getData(self::CUSTOMER_NAME);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setContent($value)
    {
        $this->setData(self::CONTENT, $value);
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getContent()
    {
        return $this->_getData(self::CONTENT);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setPriority($value)
    {
        $this->setData(self::PRIORITY_ID, $value);
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getPriority()
    {
        return $this->_getData(self::PRIORITY_ID);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setStatus($value)
    {
        $this->setData(self::STATUS_ID, $value);
        return $this;
    }

    /**
     * @return int|mixed|null
     */
    public function getStatus()
    {
        return $this->_getData(self::STATUS_ID);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setLastReplyName($value)
    {
        $this->setData(self::LAST_REPLY_NAME, $value);
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getLastReplyName()
    {
        return $this->_getData(self::LAST_REPLY_NAME);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setLastReplyDate($value)
    {
        $this->setData(self::LAST_REPLY_AT, $value);
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getLastReplyDate()
    {
        return $this->_getData(self::LAST_REPLY_AT);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setOrder($value)
    {
        $this->setData(self::ORDER, $value);
        return $this;
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setPhone($value)
    {
        $this->setData(self::PHONE, $value);
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getPhone()
    {
        return $this->getData(self::PHONE);
    }

    /**
     * @param $value
     * @return $this|TicketInterface
     */
    public function setPhoneMasked($value)
    {
        $this->setData(self::PHONE_MASKED, $value);
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getPhoneMasked()
    {
        return $this->getData(self::PHONE_MASKED);
    }

    /**
     * @return mixed|null
     */
    public function getCustomerEmailMasked()
    {
        return $this->getData(self::PHONE_MASKED);
    }

    /**
     * @param $value
     * @return Ticket
     */
    public function setCustomerEmailMasked($value)
    {
        $this->setData(self::CUSTOMER_EMAIL_MASKED, $value);
        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getOrder()
    {
        return $this->_getData(self::ORDER);
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        if ($this->category === null) {
            $this->category = $this->categoryFactory->create()->load($this->getCategoryId());
        }
        return $this->category;
    }

    /**
     * @return Team|null
     */
    public function getTeam()
    {
        if ($this->team === null) {
            $this->team = $this->teamFactory->create()->load($this->getTeamId());
        }
        return $this->team;
    }

    /**
     * @return \Magento\User\Model\User|null
     */
    public function getUser()
    {
        if ($this->user === null) {
            $this->user = $this->userFactory->create()->load($this->getUserId());
        }
        return $this->user;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        if ($this->customer === null) {
            $this->customer = $this->customerfactory->create()->load($this->getCustomerId());
        }
        return $this->customer;
    }

    /**
     * @return ResourceModel\Message\Collection
     */
    public function getMessages($sort = 'desc')
    {
        if ($this->messages === null) {
            $this->messages = $this->messageCollectionFactory->create()
                ->addFieldToFilter('ticket_id', $this->getId())
                ->setOrder('created_at', $sort);
        }
        return $this->messages;
    }

    /**
     * @return TicketExtensionInterface|\Magento\Framework\Api\ExtensionAttributesInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @param \Branch8\HelpDesk\Api\Data\TicketExtensionInterface $extensionAttributes
     * @return Message
     */
    public function setExtensionAttributes(TicketExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

}
