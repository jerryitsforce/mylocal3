<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\Adminhtml\Product;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Button\Item;
use Magento\Backend\Block\Widget\Button\ToolbarInterface;
use Magento\Backend\Block\Widget\ContainerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\User\Model\ResourceModel\User as UserResourceModel;
use Magento\User\Model\UserFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ChangeLog extends Template implements ContainerInterface
{
    /**
     * @var array|null
     */
    private ?array $currentLogEntry = null;

    /**
     * @var Registry
     */
    private Registry $coreRegistry;

    /**
     * @var ButtonList
     */
    private ButtonList $buttonList;

    /**
     * @var ToolbarInterface
     */
    private ToolbarInterface $toolbar;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var UserFactory
     */
    private UserFactory $userFactory;

    /**
     * @var UserResourceModel
     */
    private UserResourceModel $userResourceModel;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var TimezoneInterface
     */
    protected TimezoneInterface $timezone;

    /**
     * ChangeLog constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param ButtonList $buttonList
     * @param ToolbarInterface $toolbar
     * @param SerializerInterface $serializer
     * @param UserFactory $userFactory
     * @param UserResourceModel $userResourceModel
     * @param ProductRepositoryInterface $productRepository
     * @param TimezoneInterface $timezone
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        Context                    $context,
        Registry                   $coreRegistry,
        ButtonList                 $buttonList,
        ToolbarInterface           $toolbar,
        SerializerInterface        $serializer,
        UserFactory                $userFactory,
        UserResourceModel          $userResourceModel,
        ProductRepositoryInterface $productRepository,
        TimezoneInterface          $timezone,
        array                      $data = [],
        ?JsonHelper                $jsonHelper = null,
        ?DirectoryHelper           $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->coreRegistry = $coreRegistry;
        $this->buttonList = $buttonList;
        $this->toolbar = $toolbar;
        $this->serializer = $serializer;
        $this->userFactory = $userFactory;
        $this->userResourceModel = $userResourceModel;
        $this->productRepository = $productRepository;
        $this->timezone = $timezone;
    }

    /**
     * @inheritdoc
     */
    public function addButton($buttonId, $data, $level = 0, $sortOrder = 0, $region = 'toolbar'): self
    {
        $this->buttonList->add($buttonId, $data, $level, $sortOrder, $region);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function removeButton($buttonId): self
    {
        $this->buttonList->remove($buttonId);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function updateButton($buttonId, $key, $data): self
    {
        $this->buttonList->update($buttonId, $key, $data);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function canRender(Item $item): bool
    {
        return !$item->isDeleted();
    }

    /**
     * Get product name.
     *
     * @param array $logDetail
     *
     * @return string|null
     */
    public function getProductName(array $logDetail): ?string
    {
        $productName = $logDetail['product_name'] ?? null;
        if (!$productName) {
            try {
                $product = $this->productRepository->getById($logDetail['product_id']);
            } catch (NoSuchEntityException $e) {
                return null;
            }
            $productName = $product->getName();
        }
        return $productName;
    }

    /**
     * Get log entry.
     *
     * @return array
     */
    public function getLogEntry(): array
    {
        if ($this->currentLogEntry === null) {
            $this->currentLogEntry = $this->coreRegistry->registry('current_log_entry');
        }
        return $this->currentLogEntry;
    }

    /**
     * Get admin user by ID.
     *
     * @param int $userId
     *
     * @return string
     */
    public function getAdminUserById(int $userId): string
    {
        $adminUser = $this->userFactory->create();
        $this->userResourceModel->load($adminUser, $userId);

        if (!$adminUser->getId()) {
            return 'N/A';
        }

        return sprintf(
            '%s %s (%s)',
            $adminUser->getFirstname(),
            $adminUser->getLastname(),
            $adminUser->getUsername()
        );
    }

    /**
     * Format date time.
     *
     * @param string $datetime
     *
     * @return string
     */
    public function formatDatetime(string $datetime): string
    {
        if (empty($datetime)) {
            return 'N/A';
        }

        try {
            $configTimezone = $this->timezone->getConfigTimezone();
            $date = $this->timezone->date(new \DateTime($datetime))
                ->setTimezone(new \DateTimeZone($configTimezone));
        } catch (\Exception $e) {
            return 'N/A';
        }
        return $date->format('M j, Y g:i:s A');
    }

    /**
     * Return Serializer object.
     *
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout(): self
    {
        $isCopy = $this->getRequest()->getParam('copy');
        $routePath = $isCopy ? 'marketplace/product/' : ($this->_data['back_route_path'] ?? 'marketplace/product/');

        $this->buttonList->add(
            'back',
            [
                'label' => __('Back'),
                'onclick' => "setLocation('" . $this->_urlBuilder->getUrl($routePath) . "')",
                'class' => 'back'
            ]
        );
        $this->toolbar->pushButtons($this, $this->buttonList);
        return parent::_prepareLayout();
    }

    public function getCreatedFromLabel($createdFrom)
    {
        if ($createdFrom == 1)
            return __('Schedule');
        elseif ($createdFrom == 2)
            return __('Import');
        elseif ($createdFrom == 3)
            return __('Schedule Import');
        else
            return __('Seller');
    }
}
