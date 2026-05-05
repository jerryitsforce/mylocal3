<?php
namespace Branch8\Spin2Win\Rewrite\Controller\Adminhtml\Manage;

use Magento\Backend\App\Action\Context;

class SaveButton extends \Webkul\SpinToWin\Controller\Adminhtml\Manage\SaveButton
{
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $_serializer;
    /**
     * @var \Webkul\SpinToWin\Model\ButtonFactory
     */
    protected $buttonFactory;
    /**
     * @var \Webkul\SpinToWin\Helper\Data
     */
    protected $helper;

    protected $spinDraftHelper;
    
    /**
     * Constructor
     *
     * @param Context $context
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Webkul\SpinToWin\Helper\Data $helper
     * @param \Webkul\SpinToWin\Model\ButtonFactory $buttonFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Webkul\SpinToWin\Helper\Data $helper,
        \Webkul\SpinToWin\Model\ButtonFactory $buttonFactory,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper
    ) {
        parent::__construct($context, $serializer,$helper, $buttonFactory);
        $this->_serializer = $serializer;
        $this->buttonFactory = $buttonFactory;
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
                if (strpos($data['image'], '.tmp') !== false) {
                    $data['image'] = rtrim($data['image'], ".tmp");
                    $newFile = $this->helper->saveFile($data['image']);
                    $data['image'] = 'spintowin'.$newFile;
                }

                $button = $this->buttonFactory->create();
                $button->load($data['entity_id']);
                // $button->setData($data);
                // $button->save();
                $spinId = $button->getSpinId();
                unset($data['key']);
                $data['button_size'] = json_encode([
                    'desktop_width' => $data['desktop_width'],
                    'desktop_height' => $data['desktop_height'],
                    'mobile_width' => $data['mobile_width'],
                    'mobile_height' => $data['mobile_height']
                ]);
                unset($data['desktop_width']);
                unset($data['desktop_height']);
                unset($data['mobile_width']);
                unset($data['mobile_height']);

                $this->spinDraftHelper->saveDraft($spinId, 'button', $data);

                $this->getResponse()->setHeader('Content-type', 'application/javascript');
                $this->getResponse()->setBody($this->_serializer
                    ->serialize(
                        [
                            'success' => 1,
                            'message' => __('Spin to Win button data successfully saved.'),
                            'data' => $button->getData()
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
