<?php

namespace Branch8\Spin2Win\Controller\Adminhtml\Consolation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Save extends Action
{

    protected $_conn;

    protected $_serializer;

    protected $productRepo;

    /**
     * @var \Webkul\SpinToWin\Model\WheelFactory
     */
    protected $helper;

    protected $timezone;

    protected $spinDraftHelper;

    public function __construct(
        Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepo,
        \Webkul\SpinToWin\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper
    ){

        parent::__construct($context);
        $this->_conn = $resourceConnection->getConnection();
        $this->_serializer = $serializer;
        $this->productRepo = $productRepo;
        $this->helper = $helper;
        $this->timezone = $timezone;
        $this->spinDraftHelper = $spinDraftHelper;
    }

    public function execute()
    {
        $resultData = [
            'success' => 1,
            'message' => __('Consolation data successfully saved.'),
            'data' => []
        ];
        $this->getResponse()
            ->setHeader('Content-type', 'application/javascript')
            ->setHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
                true
            );
        try {
            $postData = $this->getRequest()->getPostValue();
            $spinId = $postData['spin_id'];
            $consolationSql = $this->_conn->select()
                ->from(['consolation' => 'spintowin_consolation'], ['consolation_id'])
                ->where('spin_id = ?', $spinId);
            $consolationId = $this->_conn->fetchOne($consolationSql);
            if ((int)$consolationId){
                $data['consolation_id'] = NULL;
            }
            $data = [
                'spin_id' => $postData['spin_id'],
                'consolation_fail_times' => $postData['consolation_fail_times'],
                'consolation_type' => $postData['consolation_type'],
                'consolation_title' => $postData['consolation_title'],
                'consolation_description' => $postData['consolation_description'],
                'is_active' => $postData['is_active']
            ];
            /** Lose */
            if ($postData['consolation_type'] == 0) {
                $data['rule_id'] = NULL;
                $data['pool_id'] = NULL;
                $data['sku'] = NULL;
                $data['reward_point'] = NULL;
                $data['coupon_expired_date'] = NUll;
            } else if ($postData['consolation_type'] == 1) {/** Coupon */
                $data['rule_id'] = $postData['rule_id'];
                $data['pool_id'] = NULL;
                $data['sku'] = NULL;
                $data['reward_point'] = NULL;

                if (strpos($postData['consolation_coupon_image'], '.tmp') !== false) {
                    $postData['consolation_coupon_image'] = rtrim($postData['consolation_coupon_image'], ".tmp");
                    $newFile = $this->helper->saveFile($postData['consolation_coupon_image']);
                    $data['consolation_coupon_image'] = 'spintowin'.$newFile;
                    $resultData['data']['consolation_coupon_image'] = $data['consolation_coupon_image'];
                }
                $data['coupon_expired_date'] = $postData['coupon_expired_date'];
            } else if ($postData['consolation_type'] == 2) {/** Virtual Item */
                $data['rule_id'] = NULL;
                $data['pool_id'] = $postData['pool_id'];
                $data['sku'] = NULL;
                $data['reward_point'] = NULL;
                $data['coupon_expired_date'] = NUll;
            } else if ($postData['consolation_type'] == 3) {/** Physical Item  */
                $data['rule_id'] = NULL;
                $data['pool_id'] = NULL;
                $data['sku'] = NULL;
                $data['reward_point'] = NULL;
                $data['product_name'] = NULL;
                $data['product_url'] = NULL;
                $physicalExpireDate = $this->timezone->convertConfigTimeToUtc($postData['physical_expire_date'].' 00:00:00');
                $data['physical_expire_date'] = $physicalExpireDate;

                if (strpos($postData['consolation_physical_image'], '.tmp') !== false) {
                    $postData['consolation_physical_image'] = rtrim($postData['consolation_physical_image'], ".tmp");
                    $newFile = $this->helper->saveFile($postData['consolation_physical_image']);
                    $data['consolation_physical_image'] = 'spintowin'.$newFile;
                    $resultData['data']['consolation_physical_image'] = $data['consolation_physical_image'];
                }
                $data['coupon_expired_date'] = NUll;
            } else if ($postData['consolation_type'] == 4) {/** Reward points */
                $data['rule_id'] = NULL;
                $data['pool_id'] = NULL;
                $data['sku'] = NULL;
                $data['reward_point'] = $postData['reward_point'];
                $data['coupon_expired_date'] = NUll;
                $data['point_expire_date'] = $postData['point_expire_date'];
                $data['reward_point_account'] = $postData['reward_point_account'];
                if($postData['reward_point_account'] == \Branch8\Spin2Win\Model\Config\Source\PointsAccount::TYPE_CUSTOMIZE){
                    $data['point_bu_no'] = $postData['point_bu_no'];
                    $data['point_rs_no'] = $postData['point_rs_no'];
                }
            }

            if ((int)$consolationId) {
                $data['consolation_id'] = $consolationId;
            }
            unset($data['form_key']);
            $this->spinDraftHelper->saveDraft($spinId, 'consolation', $data);

            $this->getResponse()->setBody($this->_serializer
                ->serialize(
                    $resultData
                ));
        }catch (\Exception $e){
            $this->getResponse()->setBody($this->_serializer
                ->serialize(
                    [
                        'success' => 0,
                        'message' => $e->getMessage()
                    ]
                ));
        }
        return;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_SpinToWin::manage');
    }
    public function _processUrlKeys()
    {
        return true;
    }

}
