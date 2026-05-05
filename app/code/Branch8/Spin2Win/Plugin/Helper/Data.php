<?php
namespace Branch8\Spin2Win\Plugin\Helper;

class Data {

    public function afterGetLayoutView($subject, $result){

        unset($result['slide']);
        $result['page'] = 'New Page';

        return $result;
    }

    public function aroundGetCurrentDateTime($subject, $proceed){
        return $subject->timezone->convertConfigTimeToUtc($subject->timezone->date());
    }
    public function aroundgetCurrentDate($subject, $proceed){
        return $subject->timezone->convertConfigTimeToUtc($subject->timezone->date(), 'Y-m-d');
    }

    public function aroundGetSpin($subject, $proceed){
        $currentDateTime = $subject->getCurrentDateTime();
        $spin = $subject->infoFactory->create();
        $collection = $subject->infoFactory->create()->getCollection()
                                    ->addFieldToFilter(
                                        ['start_date', 'start_date'],
                                        [
                                            ['null' => true],
                                            ['lteq' => $currentDateTime]
                                        ]
                                    )
                                    ->addFieldToFilter(
                                        ['end_date', 'end_date'],
                                        [
                                            ['null' => true],
                                            ['gt' => $currentDateTime]
                                        ]
                                    )
                                    ->addFieldToFilter('status', 1)
                                    // ->addFieldToFilter('entity_id', ['nin'=>$notInclude])
                                    ->setOrder('priority', 'DESC');
        foreach ($collection as $spinModel) {
            if (in_array($subject->store->getStore()->getWebsiteId(), explode(',', $spinModel->getWebsiteIds()))) {
                if ($spinModel->getSegments()->getSize()) {
                    $spin = $spinModel;
                    break;
                }
            }
        }
        return $spin;
    }
}