<?php
namespace Branch8\RoleDelegate\Block\Adminhtml\User;

use Branch8\RoleDelegate\Block\Adminhtml\User\Grid\DelegateUsers;
use Magento\Backend\Block\Template;

class Product extends Template
{
    public const COMM_TEMPLATE = 'Branch8_RoleDelegate::user/product.phtml';

    /**
     * @var \Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tab\Grid\Product
     */
    protected $blockGrid;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    protected $_sellerProduct;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @param \Magento\Backend\Block\Template\Context                    $context
     * @param \Magento\Framework\Registry                                $coreRegistry
     * @param \Webkul\Marketplace\Model\ResourceModel\Product\Collection $sellerProduct
     * @param \Magento\Framework\Json\EncoderInterface                   $jsonEncoder
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit          $customerEdit
     * @param array                                                      $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->jsonEncoder = $jsonEncoder;
        parent::__construct($context, $data);
    }

    /**
     * Set template to itself.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate(static::COMM_TEMPLATE);

        return $this;
    }

    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                DelegateUsers::class, 'user.delegate.product.grid');
        }
        return $this->blockGrid;
    }

    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }
}
