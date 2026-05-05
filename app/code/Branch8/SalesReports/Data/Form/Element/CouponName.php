<?php

namespace Branch8\SalesReports\Data\Form\Element;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class CouponName extends \Magento\Framework\Data\Form\Element\Multiselect
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $_backendData;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @param \Magento\Framework\Data\Form\Element\Factory $factoryElement
     * @param \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Backend\Helper\Data $backendData
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param AuthorizationInterface $authorization
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory           $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper                             $escaper,
        \Magento\Backend\Helper\Data                           $backendData,
        \Magento\Framework\View\LayoutInterface                $layout,
        \Magento\Framework\Json\EncoderInterface               $jsonEncoder,
        AuthorizationInterface                                 $authorization,
        array                                                  $data = [],
        ?SecureHtmlRenderer                                    $secureRenderer = null
    )
    {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_backendData = $backendData;
        $this->authorization = $authorization;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->_layout = $layout;
        $this->secureRenderer = $secureRenderer ?? ObjectManager::getInstance()->get(SecureHtmlRenderer::class);
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getElementHtml()
    {
        $value = (string)$this->getValue();
        $block = $this->_layout->createBlock(
            'saleRuleCouponNameOptions',
            'saleRuleCouponNameOptions',
            ['data' =>
                [
                    'input_hidden_field' => 'coupon_name_list',
                    'selected' => $value ? explode(',', $value) : [],
                    'validationUrl' => 'reports/report_sales/GetCouponNameSelected',
                    'searchUrl' => 'reports/report_sales/SearchCouponNameOptions'
                ]
            ]
        );
        $this->addClass('select multiselect admin__control-multiselect');
        $html = $block->toHtml();
        $html .= $this->getAfterElementHtml();
        return $html;
    }
}
