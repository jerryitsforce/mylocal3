<?php

namespace Branch8\Spin2Win\Rewrite\Block\Adminhtml\Segment;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Magento\SalesRule\Model\Rule;

class Edit extends \Webkul\SpinToWin\Block\Adminhtml\Segment\Edit
{
    protected $segmentType;

    protected $ruleOptions;

    protected $poolOptions;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $_urlInterface;

    public $timezone;

    protected $spinDraftHelper;

    protected $pointsAccount;

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
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper,
        \Branch8\Spin2Win\Model\Config\Source\PointsAccount $pointsAccount,
        array $data = []
    ) {

        parent::__construct($context, $registry, $formFactory, $conditions, $ruleActions,
            $ruleFactory, $segmentsFactory, $request, $data);
        $this->segmentType = $segmentType;
        $this->ruleOptions = $ruleOptions;
        $this->poolOptions = $poolOptions;
        $this->_urlInterface = $urlInterface;
        $this->timezone = $timezone;
        $this->spinDraftHelper = $spinDraftHelper;
        $this->pointsAccount = $pointsAccount;
    }
    public function _prepareForm()
    {
        $segmentDraftData = false;
        if (!$this->getRequest()->getParam('id')) {
            $segment = $this->segmentsFactory->create();
            $segment->setSpinId($this->getRequest()->getParam('spin_id'));
        } else {
            $segment = $this->segmentsFactory->create()->load($this->getRequest()->getParam('id'));
            /** Load Draft data */
            $segmentDraftData = $this->spinDraftHelper->getSegmentDraft($segment->getSpinId(), $segment->getId());
        }
        $prizeDataDB = $segment->getData();
        if($segmentDraftData){
            $prizeData = array_merge($prizeDataDB, $segmentDraftData);
        }else{
            $prizeData = $prizeDataDB;
        }
        if(!isset($prizeData['reward_point_account']) || (int)$prizeData['reward_point_account'] == 0){
            $prizeData['reward_point_account'] = 0;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('segmentrule_');
        $baseFieldset = $form->addFieldset('segment_fieldset', ['legend' => __('Information')]);

        $baseFieldset->addField('entity_id', 'hidden', ['name' => 'segment_id']);
        $baseFieldset->addField('spin_id', 'hidden', ['name' => 'spin_id']);

        $baseFieldset->addField(
            'label',
            'text',
            [
                'name' => 'label',
                'label' => __('Prize Name'),
                'id' => 'label',
                'title' => __('Label'),
                'required' => true,
                'class' => 'validate-no-html-tags',
                'note' => __('Label is shown on the wheel.')
            ]
        );

        $baseFieldset->addField(
            'heading',
            'text',
            [
                'name' => 'heading',
                'label' => __('Heading'),
                'id' => 'heading',
                'title' => __('Heading'),
                'required' => true,
                'class' => 'validate-no-html-tags',
                'note' => __('Heading is shown on result form as {{Success Title}}.')
            ]
        );

        $baseFieldset->addField(
            'description',
            'textarea',
            [
                'name' => 'description',
                'label' => __('Description'),
                'id' => 'description',
                'title' => __('Description'),
                'required' => true,
                'class' => 'validate-no-html-tags',
                'note' => __('Description is shown on result form as {{Success Description Text}}.')
            ]
        );

        $baseFieldset->addField(
            'limits',
            'text',
            [
                'name' => 'limits',
                'label' => __('Prize Qty'),
                'id' => 'limits',
                'title' => __('Prize Qty'),
                'required' => false,
                'class' => 'integer validate-zero-or-greater',
                'note' => __('Maximum number of times it can occur.')
            ]
        );
        $baseFieldset->addField(
            'stock_reminder',
            'select',
            [
                'name' => 'stock_reminder',
                'label' => __('Prize Stock Reminder'),
                'id' => 'stock_reminder',
                'title' => __('Prize Stock Reminder'),
                'required' => true,
                'values' => $this->getYesNo()
            ]
        );
        $baseFieldset->addField(
            'threshold',
            'text',
            [
                'name' => 'threshold',
                'label' => __('Prize Stock Reminder Qty'),
                'id' => 'limits',
                'title' => __('Prize Stock Reminder Qty'),
                'required' => true,
                'class' => 'integer validate-zero-or-greater'
            ]
        );
        if(isset($prizeData['gravity'])){
            $prizeData['gravity'] = (float)$prizeData['gravity'];
        }
        $baseFieldset->addField(
            'gravity',
            'text',
            [
                'name' => 'gravity',
                'label' => __('Prize Percentage'),
                'id' => 'gravity',
                'title' => __('Prize Percentage'),
                'required' => true,
                'class' => 'validate-number-range number-range-0-100',
                'note' => __('Gravity/Probability of occurrence between 0-100.')
            ]
        );

        $baseFieldset->addField(
            'position',
            'text',
            [
                'name' => 'position',
                'label' => __('Position'),
                'id' => 'position',
                'title' => __('Position'),
                'required' => true,
                'class' => 'integer validate-zero-or-greater',
                'note' => __('To manage position on the spin wheel.')
            ]
        );

        $lastfield = $baseFieldset->addField(
            'type',
            'select',
            [
                'name' => 'segment_type',
                'label' => __('Prize Type'),
                'id' => 'segment_type',
                'title' => __('Type'),
                'required' => true,
                'values' => $this->getType()
            ]
        );

        $lastfield->setAfterElementHtml(
            '<script type="text/x-magento-init">
                {
                    "*": {
                        "editsegmentload": {}
                    }
                }
            </script>'
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
                'required' => true,
                'class' => '',
                'date_format' => 'yyyy-MM',
                'min_date' =>  'new Date()',
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
                'required' => false,
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
                'class' => 'validate-length maximum-length-5',
                'note' => __('Maximum length: %1 characters', '5')
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
                'class' => 'validate-length maximum-length-10',
                'note' => __('Maximum length: %1 characters', '10')
            ]
        );

        if(isset($prizeData['physical_expire_date'])){
            $physicalExpireDate = $prizeData['physical_expire_date'];
            $physicalExpireDate = $this->timezone->date($physicalExpireDate)->format('Y-m-d');
            $prizeData['physical_expire_date'] = $physicalExpireDate;
        }else{
            $prizeData['physical_expire_date'] = '';
        }
        
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
        
        $physicalImage = '';
        if(isset($prizeData['physical_image'])){
            $physicalImage = $prizeData['physical_image'];
        }
        $physicalImageUrl = $physicalImage
                        ? $this->getMediaUrl($physicalImage):'';
        $baseFieldset->addField(
            'physical_image',
            'note', 
            [
                'label' => __('Product Image'),
                'text'  => '<div class="admin__field-control control">
                    <input type="file" id="physical_image"
                    name="physical_image" value="" class="input-text spin-files"
                    data-spin-bind-image="physical_image_preview"
                    data-upload-id="physical_image_hidden"
                    data-upload-url="'.$this->getUploadUrl().'" />
                    <input type="hidden" id="physical_image_hidden"
                    name="physical_image"
                    value="'.$physicalImage.'"/>
                    <button type="button" id="physical_image_1"
                    class="spin-button spin-upload-button spin-files-button"
                    data-triggerid="physical_image">'.__('Browse Files...').'</button>
                    <div class="spin-image-preview-container">
                        <img src="'.$physicalImageUrl.'"
                        id="banner-img" class="spin-image spin-image-preview">
                        <span class="spin-image-delete" data-delete="physical_image"></span>
                    </div>
                </div>'
            ]
        );

        $couponImage = '';
        if(isset($prizeData['coupon_image'])){
            $couponImage = $prizeData['coupon_image'];
        }
        $couponImageUrl = $couponImage
                        ? $this->getMediaUrl($couponImage):'';
        $baseFieldset->addField(
            'coupon_image',
            'note', 
            [
                'label' => __('Coupon Image'),
                'text'  => '<div class="admin__field-control control">
                    <input type="file" id="coupon_image"
                    name="coupon_image" value="" class="input-text spin-files"
                    data-spin-bind-image="coupon_image_preview"
                    data-upload-id="coupon_image_hidden"
                    data-upload-url="'.$this->getUploadUrl().'" />
                    <input type="hidden" id="coupon_image_hidden"
                    name="coupon_image"
                    value="'.$couponImage.'"/>
                    <button type="button" id="coupon_image_1"
                    class="spin-button spin-upload-button spin-files-button"
                    data-triggerid="coupon_image">'.__('Browse Files...').'</button>
                    <div class="spin-image-preview-container">
                        <img src="'.$couponImageUrl.'"
                        id="banner-img" class="spin-image spin-image-preview">
                        <span class="spin-image-delete" data-delete="coupon_image"></span>
                    </div>
                </div>'
            ]
        );

        $form->setValues($prizeData);
        $this->setForm($form);

        return $this;
    }


    /**
     * Get Actions
     *
     * @return array
     */
    private function getActions()
    {
        $applyOptions = [
            ['label' => __('Percent of product price discount'), 'value' =>  Rule::BY_PERCENT_ACTION],
            ['label' => __('Fixed amount discount'), 'value' => Rule::BY_FIXED_ACTION],
            ['label' => __('Fixed amount discount for whole cart'), 'value' => Rule::CART_FIXED_ACTION],
            ['label' => __('Buy X get Y free (discount amount is Y)'), 'value' => Rule::BUY_X_GET_Y_ACTION]
        ];
        return $applyOptions;
    }

    /**
     * Get Yes No
     *
     * @return array
     */
    private function getYesNo()
    {
        $yesNo = [
            ['label' => __('No'), 'value' => '0'],
            ['label' => __('Yes'), 'value' => '1']
        ];
        return $yesNo;
    }

    /**
     * Get Yes No
     *
     * @return array
     */
    private function getType()
    {
        $options = $this->segmentType->getAllOptions();
        return $options;
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