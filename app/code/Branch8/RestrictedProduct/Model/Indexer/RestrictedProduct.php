<?php
namespace Branch8\RestrictedProduct\Model\Indexer;

use Branch8\RestrictedProduct\Helper\Data as DataHelper;

class RestrictedProduct implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /** @var DataHelper */
    protected $dataHelper;

    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /*
     * Used by mview, allows process indexer in the "Update on schedule" mode
     */
    public function execute($ids){
        //Used by mview, allows you to process multiple placed orders in the "Update on schedule" mode
        $this->dataHelper->processUpdateProduct($ids);
    }

    /*
     * Will take all of the data and reindex
     * Will run when reindex via command line
     */
    public function executeFull(){
        //Should take into account all placed orders in the system
        $this->dataHelper->processUpdateProductFull();
    }

    /*
     * Works with a set of entity changed (may be massaction)
     */
    public function executeList(array $ids){
        //Works with a set of placed orders (mass actions and so on)
        $this->dataHelper->processUpdateProduct($ids);
    }

    /*
     * Works in runtime for a single entity using plugins
     */
    public function executeRow($id){
        //Works in runtime for a single order using plugins
        $this->dataHelper->processUpdateProduct([$id]);
    }
}