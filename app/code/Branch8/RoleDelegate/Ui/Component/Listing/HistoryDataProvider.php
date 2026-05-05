<?php
namespace Branch8\RoleDelegate\Ui\Component\Listing;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Branch8\RoleDelegate\Model\ResourceModel\Delegate\CollectionFactory;
use Magento\Framework\App\RequestInterface;

class HistoryDataProvider extends AbstractDataProvider
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param ContextInterface $context
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        ContextInterface $context,
        array $meta = [],
        array $data = []
    )
    {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collectionFactory = $collectionFactory;
        $this->request = $request;
    }

    public function getData(): array
    {
        $collection = $this->collectionFactory->create();
        $data['items'] = [];
        if ($this->request->getParam('user_id')) {
            $collection->addFieldToFilter('user_id', ['eq' => $this->request->getParam('user_id')]);
            $data = $collection->toArray();
        }
        return $data;
    }
}
