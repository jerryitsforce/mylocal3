<?php

namespace Branch8\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Catalog\Api\Data\ProductAttributeInterface;

class Visibility extends AbstractModifier
{
    /**
     * @var LocatorInterface
     */
    protected LocatorInterface $locator;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Amasty\Groupcat\Model\ResourceModel\Rule
     */
    protected $ruleResource;

    /**
     * @var UrlInterface
     */
    protected $backendUrl;

    /**
     * @var \Amasty\Groupcat\Model\RuleRepository
     */
    protected $ruleRepository;

    protected $arrayManager;

    /**
     * Construct
     *
     * @param LocatorInterface $locator
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Amasty\Groupcat\Model\ResourceModel\Rule $rule
     * @param \Amasty\Groupcat\Model\RuleRepository $ruleRepository
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     */
    public function __construct(
        LocatorInterface $locator,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Amasty\Groupcat\Model\ResourceModel\Rule $rule,
        \Amasty\Groupcat\Model\RuleRepository $ruleRepository,
        UrlInterface $backendUrl,
        ArrayManager $arrayManager
    ) {
        $this->locator = $locator;
        $this->localeDate = $localeDate;
        $this->ruleResource = $rule;
        $this->ruleRepository = $ruleRepository;
        $this->backendUrl = $backendUrl;
        $this->arrayManager = $arrayManager;
    }
    /**
     * Modify data
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * Modify meta data
     *
     * @param array $meta
     * @return void
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_replace_recursive(
            $meta,
            [
                'product_visibility' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Product Visibility'),
                                'componentType' => Fieldset::NAME,
                                'dataScope' => 'data.product.product_visibility',
                                'collapsible' => false,
                                'sortOrder' => 6,
                            ],
                        ],
                    ],
                    'children' => [
                        'assignseller_field' => $this->getProductVisibilityField()
                    ],
                ]
            ]
        );
        $meta = $this->customizeNameListeners($meta);


        return $meta;
    }
    /**
     * Prepares config for the alert price products fieldset
     * @return array
     */
    public function getProductVisibilityField()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Product Visibility'),
                        'componentType' => 'container',
                        'component' => 'Magento_Ui/js/form/components/html',
                        'additionalClasses' => 'admin__fieldset-note',
                        'content' =>
                            $this->getContent(),
                    ]
                ]
            ]
        ];
    }

    public function getContent(){
        $str = '<h5>' . __('Customer Group Catalog Rules') . '</h5>';
        $product = $this->locator->getProduct();
        $storeId = $product->getStoreId();
        $date = $this->localeDate->scopeTimeStamp($storeId);
        $connection = $this->ruleResource->getConnection();
        $table = $this->ruleResource->getTable('amasty_groupcat_rule_product');
        $select = $connection->select()
            ->from(['product' => $table])
            ->where('product_id = ?', $product->getId())
            ->group('rule_id');

        $rules = $connection->fetchAll($select);
        if($rules){
            $str .= '<p>List of active rules to hide this product for customer groups</p>';
            $str .= '<table class="data-grid"><tr><th class="data-grid-th">ID</th><th class="data-grid-th">Name</th><th class="data-grid-th">Action</th></tr>';
            foreach($rules as $rule){
                $ruleName = $this->ruleRepository->get($rule['rule_id'])->getName();
                $url = $this->backendUrl->getUrl('amasty_groupcat/rule/edit',['id' => $rule['rule_id']]);
                $str .= '<tr>';
                $str .= '<td>' . $rule['rule_id'] . '</td>';
                $str .= '<td>' . $ruleName . '</td>';
                $str .= '<td><a href="'. $url .'">Edit</a></td>';
                $str .= '</tr>';
            }
            $str .= '</table>';
            return $str;
        }
        return $str . '<div>No active rule</div>';
    }

    protected function customizeNameListeners(array $meta)
    {
        $listeners = [
            ProductAttributeInterface::CODE_SKU,
            ProductAttributeInterface::CODE_SEO_FIELD_META_TITLE,
            ProductAttributeInterface::CODE_SEO_FIELD_META_KEYWORD,
            ProductAttributeInterface::CODE_SEO_FIELD_META_DESCRIPTION,
        ];
        $textListeners = [
            ProductAttributeInterface::CODE_SEO_FIELD_META_KEYWORD,
            ProductAttributeInterface::CODE_SEO_FIELD_META_DESCRIPTION
        ];

        foreach ($listeners as $listener) {
            $listenerPath = $this->arrayManager->findPath($listener, $meta, null, 'children');
            $importsConfig = [
                'allowImport' => true,
            ];

            if (in_array($listener, $textListeners)) {
                $importsConfig['elementTmpl'] = 'ui/form/element/textarea_product';
            }

            $meta = $this->arrayManager->merge($listenerPath . static::META_CONFIG_PATH, $meta, $importsConfig);
        }

        return $meta;
    }

}
