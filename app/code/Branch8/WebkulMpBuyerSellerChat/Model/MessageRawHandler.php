<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model;

use Branch8\WebkulMpBuyerSellerChat\Model\Actions\WrapperMessage;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;

class MessageRawHandler
{
    private \Magento\Framework\Stdlib\DateTime\DateTime $date;
    private \Magento\Framework\Url\DecoderInterface $urlDecoder;
    private \Magento\Framework\Filesystem $filesystem;
    private Json $json;
    private CustomLogger $logger;
    private Escaper $escaper;

    /**
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Filesystem $filesystem
     * @param CustomLogger $logger
     * @param Json $json
     * @param Escaper $escaper
     */
    public function __construct(
        \Magento\Framework\Url\DecoderInterface     $urlDecoder,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Filesystem               $filesystem,
        CustomLogger                                $logger,
        Json                                        $json,
        Escaper $escaper
    )
    {
        $this->urlDecoder = $urlDecoder;
        $this->date = $date;
        $this->filesystem = $filesystem;
        $this->json = $json;
        $this->logger = $logger;
        $this->escaper = $escaper;
    }

    /**
     * @param array $messagesRawData
     * @return array
     */
    public function handle(array $messagesRawData)
    {
        $messages = [];
        $previousDate = '';
        foreach ($messagesRawData as $rawData) {
            $currentDate = strtotime($this->date->gmtDate('Y-m-d', $rawData['date']));
            if ($previousDate == '') {
                $previousDate = strtotime($this->date->gmtDate('Y-m-d', $rawData['date']));
            } elseif
            ($currentDate !== $previousDate) {
                $previousDate = strtotime($this->date->gmtDate('Y-m-d', $rawData['date']));
            }
            $this->handleMessageType($rawData['message_type'], $rawData['message'], $rawData);
            $messages[] = ['date' => $rawData['date'],
                'id' => $rawData['entity_id'],
                'conversationUniqueId' => $rawData['conversation_unique_id'],
                'unique_id' => $rawData['unique_id'],
                'messageType' => $rawData['message_type'],
                'message' => $this->unEscape($rawData['message']),
                'receiverName' => $rawData['receiver_name'],
                'receiverUniqueId' => $rawData['receiver_unique_id'],
                'senderName' => $rawData['sender_name'],
                'senderUniqueId' => $rawData['sender_unique_id'],
                'is_read' => (bool)$rawData['is_read'],
                'meta' => $rawData['meta'] ? $this->json->unserialize($rawData['meta']) : []
            ];
        }
        return $messages;
    }

    /**
     * @param $input
     * @return array|string|string[]|null
     */
    private function unEscape($input)
    {
        return WrapperMessage::unEscape($input);
    }

    /**
     * @param $messageType
     * @param $message
     * @param $data
     * @return void
     */
    private function handleMessageType($messageType, $message, &$data)
    {
        switch ($messageType) {
            case MessageType::FILE:
            case MessageType::IMAGE:
                $data['mediaUrl'] = $this->getFileUrl($message);
                break;
        }
    }

    /**
     * @param $message
     * @return string
     */
    private function getFileUrl($message)
    {
        //$file = $this->urlDecoder->decode($message);
        $file = $message;
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $fileName = 'marketplace/chatsystem/' . ltrim($file, '/');
        try {
            if ($directory->isFile($fileName)) {
                return '';
            }
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }

        return $fileName;
    }
}
