<?php
namespace Branch8\PromotionPage\Model\Queue;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ResourceConnection;
use Branch8\PromotionPage\Helper\Logger;

class Handler
{
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Logger $logger
     */
    public $logger;
    private CategoryRepository $categoryRepository;

    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        CategoryRepository $categoryRepository,
        ResourceConnection $resourceConnection,
        Logger $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->_categoryFactory = $categoryFactory;
        $this->logger = $logger;
        $this->categoryRepository = $categoryRepository;
    }

    public function process($message)
    {
        $data = json_decode($message, true);
        $categoryId = $data['category_id'];
        $productIds = $data['product_ids'];
        $storeId = $data['store_id'] ?? 0;

        try {
            // Load the category using _categoryFactory
            $category = $this->categoryRepository->get($categoryId, $storeId);
            $this->deleteProductByCategoryIds($categoryId);
            if (!$category->getId()) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(__('Category with id "%1" does not exist.', $categoryId));
            }

            // Prepare new product positions
            $newProducts = array_fill_keys($productIds, 0); // Set all new products to position 0

            // Merge old and new products, giving priority to new ones
            $postedProducts = $newProducts;

            // Set the new product list
            $category->setPostedProducts($postedProducts);

            // Save the category
            $category->save();

        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param $categoryId
     * @return void
     */
    public function deleteProductByCategoryIds($categoryId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('catalog_category_product');

        $connection->delete(
            $tableName,
            ['category_id = ?' => $categoryId]
        );
    }

    private function logError(\Exception $exception, $productId, $categoryId)
    {
        $this->logger->info(__FILE__.":".__LINE__);
        $this->logger->info($exception->getMessage());
        $this->logger->info('$productId '. $productId);
        $this->logger->info('$categoryId '. $categoryId);
    }
}
