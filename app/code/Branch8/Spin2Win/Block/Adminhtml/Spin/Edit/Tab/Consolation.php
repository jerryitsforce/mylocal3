<?php

namespace Branch8\Spin2Win\Block\Adminhtml\Spin\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Consolation extends Generic implements TabInterface
{
    /**
     * @var \Magento\Rule\Block\Conditions
     */
    public $conditions;

    /**
     * @var \Magento\Rule\Block\Actions
     */
    public $_ruleActions;

    /**
     * @var \Magento\Backend\Block\Template\Context
     */
    public $context;

    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;

    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    public $formFactory;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    public $ruleFactory;

    /**
     * @var \Webkul\SpinToWin\Model\SegmentsFactory
     */
    public $segmentsFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    public $request;

    protected $_conn;

    protected $wysiwygConfig;

    protected $yesnoConfig;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $_urlInterface;

    protected $timezone;

    protected $spinDraftHelper;

    protected $pointsAccount;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Rule\Block\Conditions $conditions
     * @param \Magento\Rule\Block\Actions $ruleActions
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Webkul\SpinToWin\Model\SegmentsFactory $segmentsFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Branch8\Spin2Win\Helper\Draft $spinDraftHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\Rule\Block\Actions $ruleActions,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Webkul\SpinToWin\Model\SegmentsFactory $segmentsFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Branch8\Spin2Win\Model\Config\Source\SegmentType $segmentType,
        \Branch8\Spin2Win\Model\Config\Source\RuleOptions $ruleOptions,
        \Branch8\Spin2Win\Model\Config\Source\PoolOptions $poolOptions,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Magento\Config\Model\Config\Source\Yesno $yesnoConfig,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper,
        \Branch8\Spin2Win\Model\Config\Source\PointsAccount $pointsAccount,
        array $data = []
    ) {
        $this->conditions = $conditions;
        $this->_ruleActions = $ruleActions;
        $this->ruleFactory = $ruleFactory;
        $this->segmentsFactory = $segmentsFactory;
        $this->_request = $request;
        parent::__construct($context, $registry, $formFactory, $data);
        $this->segmentType = $segmentType;
        $this->ruleOptions = $ruleOptions;
        $this->poolOptions = $poolOptions;
        $this->_conn = $resourceConnection->getConnection();
        $this->wysiwygConfig = $wysiwygConfig;
        $this->yesnoConfig = $yesnoConfig;
        $this->_urlInterface = $urlInterface;
        $this->timezone = $timezone;
        $this->spinDraftHelper = $spinDraftHelper;
        $this->pointsAccount = $pointsAccount;
    }

    /**
     * Get Tab Label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Consolation');
    }

    /**
     * Get Tab Title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Consolation');
    }

    /**
     * Can Show Tab
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Is Hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return Generic
     */
    public function _prepareForm()
    {
        $consolationData = [];
        $spinId = $this->getRequest()->getParam('id');
        if ($spinId) {
            
            $consolationQuery = $this->_conn->select()
                ->from(['consolation' => 'spintowin_consolation'])
                ->where('spin_id = ?', $spinId);
            $consolationDBData = $this->_conn->fetchRow($consolationQuery);
            if(!$consolationDBData){
                $consolationDBData = [];
            }
            
            /** Load Draft */
            $consolationDraft = $this->spinDraftHelper->getDraftByType($spinId, 'consolation');
            if($consolationDraft){
                $consolationData = array_merge($consolationDBData, $consolationDraft);
            }else{
                $consolationData = $consolationDBData;
            }
            $consolationData['spin_id'] = $spinId;
            if(isset($consolationData['physical_expire_date'])){
                $physicalExpireDate = $consolationData['physical_expire_date'];
                $consolationData['physical_expire_date'] = $this->timezone->date($physicalExpireDate)->format('Y-m-d');
            }
            if(!isset($consolationData['reward_point_account']) || (int)$consolationData['reward_point_account'] == 0){
                $consolationData['reward_point_account'] = 0;
            }
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(['id' => 'consolation_form']);
        $form->setHtmlIdPrefix('consolationtrule_');
        $form->setId('aabbcc');
        $baseFieldset = $form->addFieldset('consolation_fieldset', ['legend' => __('Consolation Information')]);

        $baseFieldset->addField('spin_id', 'hidden', ['name' => 'spin_id']);
        $baseFieldset->addField('consolation_id', 'hidden', ['name' => 'consolation_id']);

        $baseFieldset->addField(
            'is_active',
            'select',
            [
                'name' => 'is_active',
                'label' => __('Is Active'),
                'id' => 'is_active',
                'title' => __('Is Active'),
                'required' => true,
                'values' => $this->yesnoConfig->toArray()
            ]
        );

        $baseFieldset->addField(
            'consolation_fail_times',
            'text',
            [
                'name' => 'consolation_fail_times',
                'label' => __('Number of failures'),
                'id' => 'consolation_fail_time',
                'title' => __('Number of failures'),
                'required' => true,
                'class' => ''
            ]
        );

        $baseFieldset->addField(
            'consolation_type',
            'select',
            [
                'name' => 'consolation_type',
                'label' => __('Type'),
                'id' => 'consolation_type',
                'title' => __('Type'),
                'required' => true,
                'values' => $this->segmentType->getAllOptions()
            ]
        );

        /*
                 * Coupon pon select
                 */
        $baseFieldset->addField(
            'rule_id',
            'select',
            [
                'name' => 'rule_id',
                'label' => __('Rule Name'),
                'id' => 'rule_id',
                'title' => __('Rule Name'),
                'required' => true,
                'values' => array_merge(['' => __('Please select')], $this->ruleOptions->getAllOptions())
            ]
        );

        /**
         * Virtual item
         * (pool ID)
         */
        $baseFieldset->addField(
            'pool_id',
            'select',
            [
                'name' => 'pool_id',
                'label' => __('Pool Name'),
                'id' => 'pool_id',
                'title' => __('Pool Name'),
                'required' => true,
                'values' => array_merge(['' => __('Please select')], $this->poolOptions->getAllOptions())
            ]
        );
        /**
         * SKU
         */
        // $baseFieldset->addField(
        //     'sku',
        //     'text',
        //     [
        //         'name' => 'sku',
        //         'label' => __('SKU'),
        //         'id' => 'sku',
        //         'title' => __('SKU'),
        //         'required' => true,
        //         'class' => ''
        //     ]
        // );
        $baseFieldset->addField(
            'physical_expire_date',
            'date',
            [
                'name' => 'physical_expire_date',
                'label' => __('Product Expiry Date'),
                'id' => 'physical_expire_date',
                'title' => __('Product Expiry Date'),
                'required' => false,
                'class' => '',
                'date_format' => 'yyyy-MM-dd'
            ]
        );

        $baseFieldset->addField(
            'coupon_expired_date',
            'date',
            [
                'name' => 'coupon_expired_date',
                'label' => __('Coupon Expiry Date'),
                'id' => 'coupon_expired_date',
                'title' => __('Coupon Expiry Date'),
                'required' => false,
                'class' => '',
                'date_format' => 'yyyy-MM-dd'
            ]
        );

        /**
         * Reward point
         */
        $baseFieldset->addField(
            'reward_point',
            'text',
            [
                'name' => 'reward_point',
                'label' => __('Reward Point'),
                'id' => 'reward_point',
                'title' => __('Reward Point'),
                'required' => true,
                'class' => ''
            ]
        );
        $baseFieldset->addField(
            'point_expire_date',
            'date',
            [
                'name' => 'point_expire_date',
                'label' => __('Reward Point Expire month'),
                'id' => 'point_expire_date',
                'title' => __('Reward Point Expire month'),
                'required' => false,
                'class' => '',
                'date_format' => 'yyyy-MM',
                'note' => __('Maximum 23 next months')
            ]
        );
        $baseFieldset->addField(
            'reward_point_account',
            'radios',
            [
                'name' => 'reward_point_account',
                'label' => __('Points Account'),
                'id' => 'reward_point_account',
                'title' => __('Points Account'),
                'required' => true,
                'values' => $this->pointsAccount->getAllOptions()
            ]
        );
        $baseFieldset->addField(
            'point_bu_no',
            'text',
            [
                'name' => 'point_bu_no',
                'label' => __('buNo'),
                'id' => 'point_bu_no',
                'title' => __('buNo'),
                'required' => true,
                'class' => ''
            ]
        );
        $baseFieldset->addField(
            'point_rs_no',
            'text',
            [
                'name' => 'point_rs_no',
                'label' => __('rsNo'),
                'id' => 'point_rs_no',
                'title' => __('rsNo'),
                'required' => true,
                'class' => ''
            ]
        );

        $physicalImage = '';
        if(isset($consolationData['consolation_physical_image'])){
            $physicalImage = $consolationData['consolation_physical_image'];
        }
        $physicalImageUrl = $physicalImage
                        ? $this->getMediaUrl($physicalImage):'';
        $baseFieldset->addField(
            'consolation_physical_image',
            'note', 
            [
                'label' => __('Product Image'),
                'text'  => '<div class="admin__field-control control">
                    <input type="file" id="consolation_physical_image"
                    name="consolation_physical_image" value="" class="input-text spin-files"
                    data-spin-bind-image="consolation_physical_image_preview"
                    data-upload-id="consolation_physical_image_hidden"
                    data-upload-url="'.$this->getUploadUrl().'" />
                    <input type="hidden" id="consolation_physical_image_hidden"
                    name="consolation_physical_image"
                    value="'.$physicalImage.'"/>
                    <button type="button" id="consolation_physical_image_1"
                    class="spin-button spin-upload-button spin-files-button"
                    data-triggerid="consolation_physical_image">'.__('Browse Files...').'</button>
                    <div class="spin-image-preview-container">
                        <img src="'.$physicalImageUrl.'"
                        id="banner-img" class="spin-image spin-image-preview">
                        <span class="spin-image-delete" data-delete="consolation_physical_image"></span>
                    </div>
                </div>'
            ]
        );

        $couponImage = '';
        if(isset($consolationData['consolation_coupon_image'])){
            $couponImage = $consolationData['consolation_coupon_image'];
        }
        $couponImageUrl = $couponImage
                        ? $this->getMediaUrl($couponImage):'';
        $baseFieldset->addField(
            'consolation_coupon_image',
            'note', 
            [
                'label' => __('Coupon Image'),
                'text'  => '<div class="admin__field-control control">
                    <input type="file" id="consolation_coupon_image"
                    name="consolation_coupon_image" value="" class="input-text spin-files"
                    data-spin-bind-image="consolation_coupon_image_preview"
                    data-upload-id="consolation_coupon_image_hidden"
                    data-upload-url="'.$this->getUploadUrl().'" />
                    <input type="hidden" id="consolation_coupon_image_hidden"
                    name="consolation_coupon_image"
                    value="'.$couponImage.'"/>
                    <button type="button" id="consolation_coupon_image_1"
                    class="spin-button spin-upload-button spin-files-button"
                    data-triggerid="consolation_coupon_image">'.__('Browse Files...').'</button>
                    <div class="spin-image-preview-container">
                        <img src="'.$couponImageUrl.'"
                        id="banner-img" class="spin-image spin-image-preview">
                        <span class="spin-image-delete" data-delete="consolation_coupon_image"></span>
                    </div>
                </div>'
            ]
        );

        $baseFieldset->addField(
            'consolation_title',
            'text',
            [
                'name' => 'consolation_title',
                'label' => __('Title'),
                'id' => 'consolation_title',
                'title' => __('Title'),
                'required' => true,
                'class' => '',
                'note' => __('Use for frontend and email.')
            ]
        );

        $baseFieldset->addField(
            'consolation_description',
            'editor',
            [
                'name' => 'consolation_description',
                'label' => __('Description'),
                'id' => 'consolation_description',
                'title' => __('Description'),
                'required' => true,
                'class' => '',
                'wysiwyg' => true,
                'config' => $this->wysiwygConfig->getConfig()
            ]
        );

        $baseFieldset->addField(
            'save_consolation',
            'button',
            [
                'name' => 'save_consolation',
                'id' => 'save_consolation',
                'required' => false,
                'class' => 'action-default save spin-save'
            ]
        );
        $consolationData['save_consolation'] = __('Save Consolation');
        
        $form->setValues($consolationData);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * URL
     *
     * @return string
     */
    public function getUploadUrl()
    {
        return $this->_urlInterface->getUrl('catalog/product_gallery/upload');
    }

    public function getMediaUrl($filePath)
    {
        return $this->_urlBuilder->getBaseUrl([
            '_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ]).$filePath;
    }
}