<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       03/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussionCustomer\Model\Test;

use Branch8\MarketPlaceProductDiscussion\Api\Data\ThreadInterfaceFactory;
use Branch8\MarketPlaceProductDiscussion\Api\Data\MessageInterfaceFactory;
use Branch8\MarketPlaceProductDiscussion\Api\Data\ParticipantInterfaceFactory;

use Branch8\MarketPlaceProductDiscussion\Api\ThreadRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Api\MessageRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Api\ParticipantRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;

class GenerateSample
{

    private $threadFactory;
    private $messageFactory;
    private $participantFactory;

    private $threadRepository;
    private $messageRepository;
    private $participantRepository;

    public function __construct(
        ThreadInterfaceFactory         $threadFactory,
        MessageInterfaceFactory        $messageFactory,
        ParticipantInterfaceFactory    $participantFactory,
        ThreadRepositoryInterface      $threadRepository,
        MessageRepositoryInterface     $messageRepository,
        ParticipantRepositoryInterface $participantRepository
    )
    {
        $this->threadFactory = $threadFactory;
        $this->messageFactory = $messageFactory;
        $this->participantFactory = $participantFactory;

        $this->threadRepository = $threadRepository;
        $this->messageRepository = $messageRepository;
        $this->participantRepository = $participantRepository;
    }

    private function randomProduct()
    {
        /**
         * @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection
         */
        $collection = ObjectManager::getInstance()->get(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        /*$collection->addAttributeToSelect('sku')->getSelect()->limit(100);
        $ids = $collection->getAllIds();*/
        $select = "SELECT `e`.entity_id FROM `catalog_product_entity` AS `e` WHERE (e.created_in <= '1772991600') AND (e.updated_in > '1772991600') LIMIT 100";
        $ids = $collection->getConnection()->fetchCol($select);
        return[644984];
        return array_values($ids);

    }

    function randomDate($startDate, $endDate, $format = 'Y-m-d H:i:s')
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);

        $timestamp = rand($start, $end);

        return date($format, $timestamp);
    }

    public function run()
    {
        $randomProduct = $this->randomProduct();
        $customerPool = [28556]; // assume 20 customers exist
        $replied = [0, 1];
        $seller = ObjectManager::getInstance()->get(CustomerRepository::class)->getById(92837);;
        $sellerId = $seller->getId();
        $messageAuthorTye = ['seller', 'customer', 'admin'];
        $total = 50;
        for ($i = 1; $i <= $total; $i++) {
            $id = array_rand($this->randomProduct());
            $product = ObjectManager::getInstance()->get(ProductRepositoryInterface::class)->getById($randomProduct[$id]); // change to valid product
            $authorId = $customerPool[array_rand($customerPool)];
            // ---- Create Thread ----
            $thread = $this->threadFactory->create();
            $thread->setProductId((int)$product->getId());
            $thread->setSku($product->getSku());
            $thread->setProductName($product->getName());
            /*   $thread->setContent("Generated Thread $i");*/
            //$thread->setId('');
            $thread->setSellerId((int)$sellerId);
            $thread->setSellerName($seller->getFirstname() . ' ' . $seller->getLastname());
            $thread->isObjectNew(true);
            $thread->setCreatedAt($this->randomDate('2026-03-01', '2026-03-14'));
            $thread->setTitle("Auto-generated Thread #{$i}");
            $thread->setContent("產品如何保養與照顧?產品如何保養與照顧?產品如何保養與照顧?產品如何保養與照顧?產品如何保養與照顧?產品如何保養與照顧?產品如何保養與照顧?產品如何保養與照顧?產品如何保養與照顧?產品如何保養與照顧?產品如何保養與照顧?產品如何保養與照顧?");
            $thread->setAuthorType('customer');
            $thread->setAuthorName('王小明');
            $thread->setThreadType('question');
            $thread->setAuthorId(28556);
            $thread->setStatus(array_rand([Thread::STATUS_APPROVED, Thread::STATUS_PENDING]));
            $thread->setHelpfulCount(rand(0, 50));
            $isReplied = array_rand($replied);
            $thread->setData('is_seller_reply', $isReplied);
            $sellerRpliedAt = $this->randomDate('2026-03-01', '2026-03-14');
            $thread->setData('seller_replied_at', $sellerRpliedAt);
            $thread = $this->threadRepository->save($thread);
            // Register thread creator as participant
            $this->registerParticipant((int)$thread->getId(), 'customer', $authorId);
            $this->registerParticipant((int)$thread->getId(), 'seller', $sellerId);

            // ---- Create Messages ----
            if ($isReplied) {
                for ($j = 1; $j <= 1; $j++) {
                    $message = $this->messageFactory->create();
                    $message->setThreadId((int)$thread->getId());
                    $message->setMessage("Seller Replied message $j for thread $i");
                    $message->setAuthorType('seller');
                    $message->setAuthorId(489);
                    $message->setHelpfulCount(rand(0, 20));
                    $this->messageRepository->save($message);
                    // Register participant if not exists
                }
            }

        }
    }

    private function registerParticipant(int $threadId, $type, $actorId)
    {
        try {
            $participant = $this->participantFactory->create();
            $participant->setThreadId($threadId);
            $participant->setUserType('customer');
            $participant->setUserId((int)$actorId);
            $participant->setIsOwner(true);
            $this->participantRepository->save($participant);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
