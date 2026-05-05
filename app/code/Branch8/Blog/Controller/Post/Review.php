<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Post;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Branch8\Blog\Helper\Data;
use Branch8\Blog\Model\PostFactory;
use Branch8\Blog\Model\PostLikeFactory;
use Branch8\Blog\Model\ResourceModel\PostLike\Collection;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadataFactory;

/**
 * Class Review
 * @package Branch8\Blog\Controller\Post
 */
class Review extends Action
{

    /**
     * @var Data
     */
    protected $_helperBlog;

    /**
     * @var PostFactory
     */
    protected $postFactory;

    /**
     * @var PostLikeFactory
     */
    protected $_postLike;

    /**
     * @var Collection
     */
    protected $_postLikeCollection;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var PublicCookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * Review constructor.
     *
     * @param Context $context
     * @param PostFactory $postFactory
     * @param Collection $postLikeCollection
     * @param PostLikeFactory $postLikeFactory
     * @param Data $helperData
     * @param CookieManagerInterface $cookieManager
     * @param PublicCookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        Context $context,
        PostFactory $postFactory,
        Collection $postLikeCollection,
        PostLikeFactory $postLikeFactory,
        Data $helperData,
        CookieManagerInterface $cookieManager,
        PublicCookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->_helperBlog = $helperData;
        $this->_postLikeCollection = $postLikeCollection;
        $this->_postLike = $postLikeFactory;
        $this->postFactory = $postFactory;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws Exception
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('post_id');
        $action = $this->getRequest()->getParam('action');
        $mode = $this->getRequest()->getParam('mode');
        $likeId = $this->getRequest()->getParam('likeId');
        $customerId = $this->_helperBlog->getCurrentUser() ?: 0;
        $post = $this->postFactory->create()->load($id);

        if ($mode === '1') {
            $like = $this->_postLikeCollection->addFieldToFilter('entity_id', $customerId)
                ->addFieldToFilter('post_id', $post->getId());
            $likeId = $like->getFirstItem()->getId();

            if ($action === '3') {
                return $this->getResponse()->representJson(Data::jsonEncode([
                    'status' => $like->count() > 0 ? 0 : 1,
                    'action' => $like->getFirstItem()->getAction(),
                    'type' => $action
                ]));
            }

            if (!$customerId || !$post) {
                if ($action === '1') {
                    $this->messageManager->addErrorMessage(__('Can\'t Like Post.'));
                } else {
                    $this->messageManager->addErrorMessage(__('Can\'t Dislike Post.'));
                }

                return $this->getResponse()->representJson(Data::jsonEncode([
                    'status' => 0,
                    'type' => $action
                ]));
            }
        }

        try {
            $postLike = $this->_postLike->create()->load($likeId);

            if ($postLike->getId() && $postLike->getAction() === $action) {
                $postLike->delete();
                $postLike->setId(0);
            } else {
                $postLike->addData(
                    [
                        'post_id' => $post->getId(),
                        'action' => $action,
                        'entity_id' => $customerId
                    ]
                )->save();
            }

            // Update cookie and trigger section reload for guests
            if (!$customerId) {
                $cookieValue = $this->cookieManager->getCookie('blog_post_data');
                $currentPostIds = $cookieValue ? json_decode($cookieValue, true) : [];

                if ($postLike->getId()) {
                    $currentPostIds[$post->getId()] = [
                        'id' => $post->getId(),
                        'type' => $action,
                        'likeId' => $postLike->getId()
                    ];
                } else {
                    unset($currentPostIds[$post->getId()]);
                }

                $metadata = $this->cookieMetadataFactory->create()
                    ->setDuration(60 * 60 * 24 * 365)
                    ->setPath('/')
                    ->setHttpOnly(true)
                    ->setSecure($this->getRequest()->isSecure())
                    ->setSameSite('Strict');

                $this->cookieManager->setPublicCookie(
                    'blog_post_data',
                    json_encode($currentPostIds),
                    $metadata
                );

                // Trigger CustomerData reload
                $sectionMetadata = $this->cookieMetadataFactory->create()
                    ->setDuration(3600)
                    ->setPath('/')
                    ->setHttpOnly(false); // Must be readable by Magento JS to detect change
                $this->cookieManager->setPublicCookie(
                    'section_data_ids',
                    json_encode(['blog_post_data' => time()]),
                    $sectionMetadata
                );
            }

            $sumLike = $this->_postLike->create()->getCollection()->addFieldToFilter('action', '1')
                ->addFieldToFilter('post_id', $id);
            $sumDislike = $this->_postLike->create()->getCollection()->addFieldToFilter('action', '0')
                ->addFieldToFilter('post_id', $id);

            return $this->getResponse()->representJson(Data::jsonEncode([
                'status' => 1,
                'type' => $action,
                'sumLike' => $sumLike->count(),
                'sumDislike' => $sumDislike->count(),
                'postLike' => $postLike->getId()
            ]));
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());

            return $this->getResponse()->representJson(Data::jsonEncode([
                'status' => 0,
                'type' => $action
            ]));
        }
    }
}
