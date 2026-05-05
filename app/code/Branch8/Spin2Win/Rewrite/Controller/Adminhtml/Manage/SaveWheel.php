<?php
namespace Branch8\Spin2Win\Rewrite\Controller\Adminhtml\Manage;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class SaveWheel extends Action
{
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $_serializer;
    /**
     * @var \Webkul\SpinToWin\Helper\Data
     */
    protected $wheelFactory;
    /**
     * @var \Webkul\SpinToWin\Model\WheelFactory
     */
    protected $helper;

    protected $spinDraftHelper;
    
    /**
     * Constructor
     *
     * @param Context $context
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Webkul\SpinToWin\Helper\Data $helper
     * @param \Webkul\SpinToWin\Model\WheelFactory $wheelFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Webkul\SpinToWin\Helper\Data $helper,
        \Webkul\SpinToWin\Model\WheelFactory $wheelFactory,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper
    ) {
        parent::__construct($context);
        $this->_serializer = $serializer;
        $this->wheelFactory = $wheelFactory;
        $this->helper = $helper;
        $this->spinDraftHelper = $spinDraftHelper;
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();
            if (!empty($data) && isset($data['entity_id'])) {

                $wheelData = $this->wheelFactory->create();
                $wheelData->load($data['entity_id']);
                $spinId = $wheelData->getSpinId();
                if (strpos($data['center_image'], '.tmp') !== false) {
                    $data['center_image'] = rtrim($data['center_image'], ".tmp");
                    $newFile = $this->helper->saveFile($data['center_image']);
                    $data['center_image'] = 'spintowin'.$newFile;
                }
                // if (strpos($data['background_image'], '.tmp') !== false) {
                //     $data['background_image'] = rtrim($data['background_image'], ".tmp");
                //     $newFile = $this->helper->saveFile($data['background_image']);
                //     $data['background_image'] = 'spintowin'.$newFile;
                // }
                if (strpos($data['pin_image'], '.tmp') !== false) {
                    $data['pin_image'] = rtrim($data['pin_image'], ".tmp");
                    $newFile = $this->helper->saveFile($data['pin_image']);
                    $data['pin_image'] = 'spintowin'.$newFile;
                }
                // $wheelData = $this->wheelFactory->create();
                // $wheelData->load($data['entity_id']);
                // $wheelData->setData($data);
                // $wheelData->save();
                unset($data['key']);
                $this->spinDraftHelper->saveDraft($spinId, 'wheel', $data);

                $this->getResponse()->setHeader('Content-type', 'application/javascript');
                $this->getResponse()->setBody($this->_serializer
                    ->serialize(
                        [
                            'success' => 1,
                            'message' => __('Spin wheel data successfully saved.'),
                            'data' => $wheelData->getData()
                        ]
                    ));
                return;
            } else {
                $this->getResponse()->setHeader('Content-type', 'application/javascript');
                $this->getResponse()->setBody($this->_serializer
                    ->serialize(
                        [
                            'success' => 0,
                            'message' => __('Invalid data.')
                        ]
                    ));
                return;
            }
        } catch (\Exception $e) {
            $this->getResponse()->setHeader('Content-type', 'application/javascript');
            $this->getResponse()->setBody($this->_serializer
                    ->serialize(
                        [
                            'success' => 0,
                            'message' => $e->getMessage()
                        ]
                    ));
            return;
        }
    }
}
