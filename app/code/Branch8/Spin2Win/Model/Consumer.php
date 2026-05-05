<?php
namespace Branch8\Spin2Win\Model;
use Magento\Framework\MessageQueue\ConsumerConfiguration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Mail\Template\TransportBuilder;


class Consumer extends ConsumerConfiguration{
    const CONSUMER_NAME = "spin2win.prize.email";
    const QUEUE_NAME = "spin2win.prize.email";

    protected $scopeConfig;

    protected $transportBuilder;

    protected $inlineTranslation;

    protected $logger;


    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        \Webkul\SpinToWin\Logger\Logger $logger, 
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ){
        $this->logger = $logger;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
    }

    public function process($request){
        $data = json_decode($request, true);

        $type = $data['type'];
        if($type == 'prize'){
            $this->sendPrize($data['data']);
        }
        if($type == 'consolation'){
            $this->sendConsolation($data['data']);
        }
        if($type == 'low_stock'){
            $this->lowStock($data['data']);
        }
    }


    public function sendPrize($data){
        $sender = [
            'name' => $this->scopeConfig->getValue('trans_email/ident_general/name'),
            'email' => $this->scopeConfig->getValue('trans_email/ident_general/email')
        ];
        $templateId = $this->scopeConfig->getValue('spin_to_win/general/send_prize_to_customer');
        $receiverInfo = ['name' => $data['customer_name'], 'email' => $data['customer_email']];

        $emailData = [
            'subject' => 'Spin to Win notification',
            'name' => $data['customer_name'],
            'segmentlabel' => $data['label'],
            'heading' => $data['heading'],
            'description' => $data['description'],
            'type' => $data['type'],
            'coupon' => $data['coupon'],
            'serial' => $data['serial'],
            'point' => $data['point'],
            'product_name' => $data['product_name'],
            'product_url' => $data['product_url'],
            'product_infor' => '<a href="'.$data['product_url'].'">'.$data['product_name'].'</a>'
        ];
        $this->sendNotification($receiverInfo, $sender, $templateId, $emailData);
    }

    public function sendConsolation($data){
        $sender = [
            'name' => $this->scopeConfig->getValue('trans_email/ident_general/name'),
            'email' => $this->scopeConfig->getValue('trans_email/ident_general/email')
        ];
        $templateId = $this->scopeConfig->getValue('spin_to_win/general/send_consolation_to_customer');
        $receiverInfo = ['name' => $data['customer_name'], 'email' => $data['customer_email']];

        $emailData = [
            'subject' => 'Spin to Win consolation notification',
            'name' => $data['customer_name'],
            'segmentlabel' => $data['label'],
            'heading' => $data['heading'],
            'description' => $data['description'],
            'type' => $data['type'],
            'coupon' => $data['coupon'],
            'serial' => $data['serial'],
            'point' => $data['point'],
            'product_name' => $data['product_name'],
            'product_url' => $data['product_url'],
            'product_infor' => '<a href="'.$data['product_url'].'">'.$data['product_name'].'</a>'
        ];
        $this->sendNotification($receiverInfo, $sender, $templateId, $emailData);
    }

    public function lowStock($data){
        $sender = [
            'name' => $this->scopeConfig->getValue('trans_email/ident_general/name'),
            'email' => $this->scopeConfig->getValue('trans_email/ident_general/email')
        ];
        $templateId = $this->scopeConfig->getValue('spin_to_win/general/send_low_stock');

        $emails = $data['emails'];
        $emailArr = explode(',', (string)$emails);
        foreach($emailArr as $_email){
            $receiverInfo = ['name' => 'Admin', 'email' => $_email];

            $emailData = [
                'subject' => 'Prize low stock notification',
                'event_name' => $data['event_name'],
                'segmentlabel' => $data['segment_name'],
                'threshold' => $data['threshold'],
                'remain_qty' => $data['remain_qty'],
                'name' => 'Admin'
            ];
            $this->sendNotification($receiverInfo, $sender, $templateId, $emailData);
        }
    }

    public function sendNotification($receiverInfo, $senderInfo, $templateId, $emailTempVariables)
    {
        try {
            $this->generateTemplate(
                $emailTempVariables,
                $senderInfo,
                $receiverInfo,
                $templateId
            );
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    public function generateTemplate(
        $emailTemplateVariables,
        $senderInfo,
        $receiverInfo,
        $emailTempId
    ) {
        $area = \Magento\Framework\App\Area::AREA_FRONTEND;
        try {
            $this->transportBuilder->setTemplateIdentifier($emailTempId)->setTemplateOptions(
                ['area' => $area, 'store' => 1]
            )->setTemplateVars($emailTemplateVariables)->setFrom($senderInfo)->addTo(
                $receiverInfo['email'],
                $receiverInfo['name']
            );
            return $this;
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}