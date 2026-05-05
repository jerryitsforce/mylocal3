<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model;

use Magento\Customer\Model\Address\AddressModelInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory;
use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderAddressInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;

class ParentOrderAddress extends \Magento\Framework\Model\AbstractExtensibleModel
    implements ParentOrderAddressInterface, AddressModelInterface
{
    private $parentOrder;

    private $parentOrderFactory;
    private $regionFactory;

    public const TYPE_BILLING = 'billing';

    public const TYPE_SHIPPING = 'shipping';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param ParentOrderFactory $parentOrderFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        ExtensionAttributesFactory                              $extensionFactory,
        AttributeValueFactory                                   $customAttributeFactory,
        ParentOrderFactory                                      $parentOrderFactory,
        \Magento\Directory\Model\RegionFactory                  $regionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = []
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
        $this->parentOrderFactory = $parentOrderFactory;
        $this->regionFactory = $regionFactory;
    }

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderAddress::class
        );
    }

    /**
     * @param ParentOrder $parentOrder
     * @return $this
     */
    public function setParentOrder(ParentOrder $parentOrder)
    {
        $this->parentOrder = $parentOrder;
        return $this;
    }

    /**
     * Return 2 letter state code if available, otherwise full region name
     *
     * @return null|string
     */
    public function getRegionCode()
    {
        $regionId = (!$this->getRegionId() && is_numeric($this->getRegion())) ?
            $this->getRegion() :
            $this->getRegionId();
        $model = $this->regionFactory->create()->load($regionId);
        if ($model->getCountryId() == $this->getCountryId()) {
            return $model->getCode();
        } elseif (is_string($this->getRegion())) {
            return $this->getRegion();
        } else {
            return null;
        }
    }

    /**
     * Get full customer name
     *
     * @return string
     */
    public function getName()
    {
        $name = '';
        if ($this->getPrefix()) {
            $name .= __($this->getPrefix()) . ' ';
        }
        $name .= $this->getFirstname();
        if ($this->getMiddlename()) {
            $name .= ' ' . $this->getMiddlename();
        }
        $name .= ' ' . $this->getLastname();
        if ($this->getSuffix()) {
            $name .= ' ' . __($this->getSuffix());
        }
        return $name;
    }

    /**
     * Combine values of street lines into a single string
     *
     * @param string[]|string $value
     * @return string
     */
    protected function implodeStreetValue($value)
    {
        if (is_array($value)) {
            $value = trim(implode(PHP_EOL, $value));
        }
        return $value;
    }

    /**
     * Enforce format of the street field
     *
     * @param array|string $key
     * @param array|string $value
     *
     * @return \Magento\Framework\DataObject
     */
    public function setData($key, $value = null)
    {
        if (is_array($key)) {
            $key = $this->implodeStreetField($key);
        } elseif ($key == ParentOrderAddressInterface::STREET) {
            $value = $this->implodeStreetValue($value);
        }
        return parent::setData($key, $value);
    }

    /**
     * Implode value of the street field, if it is present among other fields
     *
     * @param array $data
     * @return array
     */
    protected function implodeStreetField(array $data)
    {
        if (array_key_exists(ParentOrderAddressInterface::STREET, $data)) {
            $data[ParentOrderAddressInterface::STREET] = $this->implodeStreetValue($data[ParentOrderAddressInterface::STREET]);
        }
        return $data;
    }

    /**
     * Create fields street1, street2, etc.
     *
     * To be used in controllers for views data
     *
     * @return $this
     */
    public function explodeStreetAddress()
    {
        $streetLines = $this->getStreet();
        foreach ($streetLines as $lineNumber => $lineValue) {
            $this->setData(ParentOrderAddressInterface::STREET . ($lineNumber + 1), $lineValue);
        }
        return $this;
    }

    /**
     * Get order
     *
     * @return ParentOrder
     */
    public function getParentOrder()
    {
        if (!$this->parentOrder) {
            $this->parentOrder = $this->parentOrderFactory->create()->load($this->getParentId());
        }
        return $this->parentOrder;
    }

    /**
     * Retrieve street field of an address
     *
     * @return string[]
     */
    public function getStreet()
    {
        if (is_array($this->getData(ParentOrderAddressInterface::STREET))) {
            return $this->getData(ParentOrderAddressInterface::STREET);
        }
        return explode(PHP_EOL, $this->getData(ParentOrderAddressInterface::STREET) ?? '');
    }

    /**
     * Get street line by number
     *
     * @param int $number
     * @return string
     */
    public function getStreetLine($number)
    {
        $lines = $this->getStreet();
        return $lines[$number - 1] ?? '';
    }

    //@codeCoverageIgnoreStart

    /**
     * Returns address_type
     *
     * @return string
     */
    public function getAddressType()
    {
        return $this->getData(ParentOrderAddressInterface::ADDRESS_TYPE);
    }

    /**
     * Returns city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->getData(ParentOrderAddressInterface::CITY);
    }

    /**
     * Returns company
     *
     * @return string
     */
    public function getCompany()
    {
        return $this->getData(ParentOrderAddressInterface::COMPANY);
    }

    /**
     * Returns country_id
     *
     * @return string
     */
    public function getCountryId()
    {
        return $this->getData(ParentOrderAddressInterface::COUNTRY_ID);
    }

    /**
     * Returns customer_address_id
     *
     * @return int
     */
    public function getCustomerAddressId()
    {
        return $this->getData(ParentOrderAddressInterface::CUSTOMER_ADDRESS_ID);
    }

    /**
     * Returns customer_id
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->getData(ParentOrderAddressInterface::CUSTOMER_ID);
    }

    /**
     * Returns email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->getData(ParentOrderAddressInterface::EMAIL);
    }

    /**
     * Returns entity_id
     *
     * @return int
     */
    public function getEntityId()
    {
        return $this->getData(ParentOrderAddressInterface::ENTITY_ID);
    }

    /**
     * Sets the ID for the order address.
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        return $this->setData(ParentOrderAddressInterface::ENTITY_ID, $entityId);
    }

    /**
     * Returns fax
     *
     * @return string
     */
    public function getFax()
    {
        return $this->getData(ParentOrderAddressInterface::FAX);
    }

    /**
     * Returns firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->getData(ParentOrderAddressInterface::FIRSTNAME);
    }

    /**
     * Returns lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->getData(ParentOrderAddressInterface::LASTNAME);
    }

    /**
     * Returns middlename
     *
     * @return string
     */
    public function getMiddlename()
    {
        return $this->getData(ParentOrderAddressInterface::MIDDLENAME);
    }

    /**
     * Returns parent_id
     *
     * @return int
     */
    public function getParentOrderId()
    {
        return $this->getData(ParentOrderAddressInterface::PARENT_ORDER_ID);
    }

    /**
     * Returns postcode
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->getData(ParentOrderAddressInterface::POSTCODE);
    }

    /**
     * Returns prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->getData(ParentOrderAddressInterface::PREFIX);
    }

    /**
     * Returns region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->getData(ParentOrderAddressInterface::REGION);
    }

    /**
     * Returns region_id
     *
     * @return int
     */
    public function getRegionId()
    {
        return $this->getData(ParentOrderAddressInterface::REGION_ID);
    }

    /**
     * Returns suffix
     *
     * @return string
     */
    public function getSuffix()
    {
        return $this->getData(ParentOrderAddressInterface::SUFFIX);
    }

    /**
     * Returns telephone
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->getData(ParentOrderAddressInterface::TELEPHONE);
    }

    /**
     * Returns vat_id
     *
     * @return string
     */
    public function getVatId()
    {
        return $this->getData(ParentOrderAddressInterface::VAT_ID);
    }

    /**
     * Returns vat_is_valid
     *
     * @return int
     */
    public function getVatIsValid()
    {
        return $this->getData(ParentOrderAddressInterface::VAT_IS_VALID);
    }

    /**
     * Returns vat_request_date
     *
     * @return string
     */
    public function getVatRequestDate()
    {
        return $this->getData(ParentOrderAddressInterface::VAT_REQUEST_DATE);
    }

    /**
     * Returns vat_request_id
     *
     * @return string
     */
    public function getVatRequestId()
    {
        return $this->getData(ParentOrderAddressInterface::VAT_REQUEST_ID);
    }

    /**
     * Returns vat_request_success
     *
     * @return int
     */
    public function getVatRequestSuccess()
    {
        return $this->getData(ParentOrderAddressInterface::VAT_REQUEST_SUCCESS);
    }

    /**
     * @inheritdoc
     */
    public function setParentOrderId($id)
    {
        return $this->setData(ParentOrderAddressInterface::PARENT_ORDER_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerAddressId($id)
    {
        return $this->setData(ParentOrderAddressInterface::CUSTOMER_ADDRESS_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function setRegionId($id)
    {
        return $this->setData(ParentOrderAddressInterface::REGION_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function setStreet($street)
    {
        return $this->setData(ParentOrderAddressInterface::STREET, $street);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($id)
    {
        return $this->setData(ParentOrderAddressInterface::CUSTOMER_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function setFax($fax)
    {
        return $this->setData(ParentOrderAddressInterface::FAX, $fax);
    }

    /**
     * @inheritdoc
     */
    public function setRegion($region)
    {
        return $this->setData(ParentOrderAddressInterface::REGION, $region);
    }

    /**
     * @inheritdoc
     */
    public function setPostcode($postcode)
    {
        return $this->setData(ParentOrderAddressInterface::POSTCODE, $postcode);
    }

    /**
     * @inheritdoc
     */
    public function setLastname($lastname)
    {
        return $this->setData(ParentOrderAddressInterface::LASTNAME, $lastname);
    }

    /**
     * @inheritdoc
     */
    public function setCity($city)
    {
        return $this->setData(ParentOrderAddressInterface::CITY, $city);
    }

    /**
     * @inheritdoc
     */
    public function setEmail($email)
    {
        return $this->setData(ParentOrderAddressInterface::EMAIL, $email);
    }

    /**
     * @inheritdoc
     */
    public function setTelephone($telephone)
    {
        return $this->setData(ParentOrderAddressInterface::TELEPHONE, trim($telephone ?: ''));
    }

    /**
     * @inheritdoc
     */
    public function setCountryId($id)
    {
        return $this->setData(ParentOrderAddressInterface::COUNTRY_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function setFirstname($firstname)
    {
        return $this->setData(ParentOrderAddressInterface::FIRSTNAME, $firstname);
    }

    /**
     * @inheritdoc
     */
    public function setAddressType($addressType)
    {
        return $this->setData(ParentOrderAddressInterface::ADDRESS_TYPE, $addressType);
    }

    /**
     * @inheritdoc
     */
    public function setPrefix($prefix)
    {
        return $this->setData(ParentOrderAddressInterface::PREFIX, $prefix);
    }

    /**
     * @inheritdoc
     */
    public function setMiddlename($middlename)
    {
        return $this->setData(ParentOrderAddressInterface::MIDDLENAME, $middlename);
    }

    /**
     * @inheritdoc
     */
    public function setSuffix($suffix)
    {
        return $this->setData(ParentOrderAddressInterface::SUFFIX, $suffix);
    }

    /**
     * @inheritdoc
     */
    public function setCompany($company)
    {
        return $this->setData(ParentOrderAddressInterface::COMPANY, $company);
    }

    /**
     * @inheritdoc
     */
    public function setVatId($id)
    {
        return $this->setData(ParentOrderAddressInterface::VAT_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function setVatIsValid($vatIsValid)
    {
        return $this->setData(ParentOrderAddressInterface::VAT_IS_VALID, $vatIsValid);
    }

    /**
     * @inheritdoc
     */
    public function setVatRequestId($id)
    {
        return $this->setData(ParentOrderAddressInterface::VAT_REQUEST_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function setRegionCode($regionCode)
    {
        return $this->setData(ParentOrderAddressInterface::KEY_REGION_CODE, $regionCode);
    }

    /**
     * @inheritdoc
     */
    public function setVatRequestDate($vatRequestDate)
    {
        return $this->setData(ParentOrderAddressInterface::VAT_REQUEST_DATE, $vatRequestDate);
    }

    /**
     * @inheritdoc
     */
    public function setVatRequestSuccess($vatRequestSuccess)
    {
        return $this->setData(ParentOrderAddressInterface::VAT_REQUEST_SUCCESS, $vatRequestSuccess);
    }

    /**
     * @inheritdoc
     *
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderAddressExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     *
     * @param \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderAddressExtensionInterface $extensionAttributes
     *
     * @return $this
     */
    public function setExtensionAttributes(\Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderAddressExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * @inheritdoc
     * @since 102.0.3
     */
    public function beforeSave()
    {
        if ($this->getEmail() === null) {
            $this->setEmail($this->getParentOrder()->getExtensionAttributes()->getDetail()->getCustomerEmail());
            return parent::beforeSave();
        }
    }
}
