<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatAdminUi\Ui\Component\Listing\Columns;

use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetProfileImageLink;
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
class ChatProfile extends Column
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
     * @var GetProfileImageLink
     */
    protected $getProfileImageLink;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     * @param \Magento\Framework\Filesystem $filesystem
     * @param GetProfileImageLink $getProfileImageLink
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                        $context,
        UiComponentFactory                      $uiComponentFactory,
        UrlInterface                            $urlBuilder,
        \Magento\Framework\Url\DecoderInterface $urlDecoder,
        \Magento\Framework\Filesystem           $filesystem,
        GetProfileImageLink                     $getProfileImageLink,
        array                                   $components = [],
        array                                   $data = []
    )
    {
        $this->_urlBuilder = $urlBuilder;
        $this->filesystem = $filesystem;
        $this->urlDecoder = $urlDecoder;
        $this->getProfileImageLink = $getProfileImageLink;
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
                    $link = '<img width="50px" height="50px" src="' . $this->getProfileImageLink->get($item['image']) . '"/>';
                    $item['image'] = $link;
                }
            }
        }
        return $dataSource;
    }
}
