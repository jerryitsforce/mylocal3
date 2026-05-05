<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatAdminUi\Ui\Component\Listing\Columns;

use Branch8\WebkulMpBuyerSellerChat\Model\MessageType;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File as FilesystemIo;

/**
 * Class ViewAction.
 */
class Message extends Column
{
    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;

    /**
     * @var FilesystemIo
     */
    protected $filesystemIo;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     * @param \Magento\Framework\Filesystem $filesystem
     * @param FilesystemIo $filesystemIo
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                        $context,
        UiComponentFactory                      $uiComponentFactory,
        UrlInterface                            $urlBuilder,
        \Magento\Framework\Url\DecoderInterface $urlDecoder,
        \Magento\Framework\Filesystem           $filesystem,
        FilesystemIo                            $filesystemIo,
        array                                   $components = [],
        array                                   $data = []
    )
    {
        $this->_urlBuilder = $urlBuilder;
        $this->filesystem = $filesystem;
        $this->urlDecoder = $urlDecoder;
        $this->filesystemIo = $filesystemIo;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    $isMedia = in_array($item['message_type'], MessageType::getValidTypes());
                    if ($isMedia && ($meta = $this->getMeta($item))) {
                        $name = $item[$this->getData('name')];
                        $html = $this->getHtml($item['message_type'], $meta);
                        $item[$this->getData('name')] = $html;
                    }
                }
            }
        }
        return $dataSource;
    }

    /**
     * @param $type
     * @param $meta
     * @return string
     */
    private function getHtml($type, $meta)
    {
        $html = '';
        switch ($type) {
            case MessageType::IMAGE:
                $html .= '<img src="' . $meta['url'] . '" style="max-width:120px;max-height:120px"/>';
                $html .= '<br/>';
                $html .= '<a target="_blank" href="' . $meta['url'] . '">' . $meta['name'] . '</a>';
                break;
            case MessageType::VIDEO:
                $html .= '<video controls  style="max-width:120px;max-height:120px">';
                $html .= '<source src="' . $meta['url'] . '" type="' . $meta['type'] . '"/>';
                $html .= '</video>';
                $html .= '<br/>';
                $html .= '<a target="_blank" href="' . $meta['url'] . '">' . $meta['name'] . '</a>';
                break;
        }
        return $html;
    }

    /**
     * @param $message
     * @return mixed|string
     */
    private function getMeta($message)
    {
        $outPutMeta = [];
        try {
            $metadata = json_decode((string)$message['meta'], true);
            foreach ($metadata as $meta) {
                if ($meta['key'] === 'url') {
                    $outPutMeta['url'] = $meta['value'];
                }
                if ($meta['key'] === 'type') {
                    $outPutMeta['type'] = $meta['value'];
                }
                if ($meta['key'] === 'name') {
                    $outPutMeta['name'] = $meta['value'];
                }
            }
            return $outPutMeta;
        } catch (\Exception $exception) {

        }
        return $outPutMeta;
    }
}
