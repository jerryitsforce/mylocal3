<?php

namespace Branch8\EventTicket\Ui\DataProvider;
use Branch8\EventTicket\Model\ResourceModel\TicketEvent\CollectionFactory;
use Magento\Framework\Registry;

class FormDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider{

    /**
     * @var array
     */
    protected $_loadedData;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serialize;

    protected $registry;
    
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        \Magento\Framework\Serialize\Serializer\Json $serialize,
        Registry $registry,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->serialize = $serialize;
        $this->registry = $registry;
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->_loadedData)) {
            return $this->_loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $event) {
            if(isset($event->getData()['image_url']))
            {
                try
                {
                    $event->setData('image_url',$this->serialize->unserialize($event->getImageUrl()));
                }catch (\Exception $e){
                    $event->setData('image_url','');
                }
            }
            $this->_loadedData[$event->getId()] = $event->getData();
            $this->registry->register('current_pool', $event->getId());

            $poolSerialCntSql = 'select count(*) as cnt from ticket_event_ticket where event_id='.$event->getId();
            $conn = $this->collection->getConnection();
            $cnt = $conn->fetchOne($poolSerialCntSql);
            $this->registry->register('pool_has_serial', $cnt);
        }

        return $this->_loadedData;
    }

}