<?php

namespace Branch8\Spin2Win\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Draft extends AbstractHelper
{

    protected $timezone;

    protected $_conn;

    protected $_draft = NULL;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->_conn = $resourceConnection->getConnection();
    }

    public function saveDraft($spinId, $field, $data){
        /** Save Draft */
        $spinDraftSelect = $this->_conn->select()
        ->from(['draft' => 'spintowin_draft'])
        ->where('spin_id = ?', $spinId);
        $spinDraftRowData = $this->_conn->fetchRow($spinDraftSelect);
        unset($data['entity_id']);
        unset($data['form_key']);
        if(empty($spinDraftRowData)){
            $spinDraftRowData = [
                'draft_id' => NULL,
                'spin_id' => $spinId,
                'data' => json_encode([$field => $data]),
                'updated_at' => $this->timezone->convertConfigTimeToUtc($this->timezone->date())
            ];
            $this->_conn->insert('spintowin_draft', $spinDraftRowData);
        }else{
            $spinDraftData = json_decode($spinDraftRowData['data'], true);
            $spinDraftData[$field] = $data;
            $spinDraftRowData['data'] = json_encode($spinDraftData);
            $spinDraftRowData['updated_at'] = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            unset($spinDraftRowData['draft_id']);
            unset($spinDraftRowData['spin_id']);
            $this->_conn->update('spintowin_draft', $spinDraftRowData, 'spin_id = '.$spinId);
        }
    }

    public function getDraft($spinId, $reload=false){
        if($reload || $this->_draft == null){
            $draftSelect = $this->_conn->select()
                ->from(['draft' => 'spintowin_draft'])
                ->where('spin_id = ?', $spinId);
            $draft = $this->_conn->fetchRow($draftSelect);
            $this->_draft = $draft;
        }
        
        return $this->_draft;
    }
    public function getDraftByType($spinId, $type){
        $spinDraft = $this->getDraft($spinId);
        if(empty($spinDraft)){
            return false;
        }
        try{
            $draftData = json_decode((string)$spinDraft['data'], true);
        }catch(\Exception $e){
            return false;
        }
        if(!isset($draftData[$type])){
            return false;
        }
        return $draftData[$type];
    }

    public function getSegmentDraft($spinId, $segmentId){
        $segmentsDraft = $this->getDraftByType($spinId, 'segments');
        if(!$segmentsDraft){
            return false;
        }
        if(!isset($segmentsDraft[$segmentId])){
            return false;
        }

        return $segmentsDraft[$segmentId];
    }

    public function checkPrize100Invalid($spinId){
        if(!(int)$spinId){
            return [
                'result' => false
            ];
        }
        $prizeSelect = $this->_conn->select()
            ->from('spintowin_segments', ['entity_id', 'gravity'])
            ->where('spin_id = ?', $spinId);
        $prizes = $this->_conn->fetchAll($prizeSelect);
        $prizeData = [];
        foreach($prizes as $_prize){
            $prizeData[$_prize['entity_id']] = $_prize['gravity'];
        }
        /** Load Draft */
        $draftSegment = $this->getDraftByType($spinId, 'segments');
        if($draftSegment){
            foreach($draftSegment as $_seg){
                $prizeData[$_seg['entity_id']] = $_seg['gravity'];
            }
        }
        $total = 0;
        $deletedSegments = $this->getDeletedSegment($spinId);
        $data = [];
        $decimalCnt = 0;
        foreach($prizeData as $pKey => $_pr){
            if(in_array($pKey, $deletedSegments)){
                continue;
            }
            $flVal = (float)$_pr;
            $exFlVal = explode('.', $flVal);
            if(isset($exFlVal['1']) && strlen($exFlVal['1']) > $decimalCnt){
                $decimalCnt = strlen($exFlVal['1']);
            }
            $data[] = $flVal;
            $total += $flVal;
        }
        if(count($data) == 0){
            return [
                'result' => true,
                'total' => 0
            ];
        }else if(count($data) == 1){
            return [
                'result' => !($data[0] == 100),
                'total' => $data[0]
            ];
        }else{
            $bcaddStr = '';
            for($i = 1; $i < count($data); ++ $i){
                if($i == 1){
                    $bcaddStr = 'bcadd("'.$data[0].'", "'.$data[$i].'", '.$decimalCnt.'),';
                }else{
                    $bcaddStr = 'bcadd('.$bcaddStr.' "'.$data[$i].'", '.$decimalCnt.'),';
                }
            }
            $bcaddStr = '$totalValue = '.substr($bcaddStr, 0, -1).';';
            eval($bcaddStr);
            if(bccomp($totalValue, 100, $decimalCnt)){
                return [
                    'result' => true,
                    'total' => (float)$totalValue
                ];
            }else{
                return [
                    'result' => false,
                    'total' => (float)$totalValue
                ];
            }
            
        }
    }

    public function addDeleteSegment($spinId, $segmentId){
        $spinDraft = $this->getDraft($spinId);

        if(empty($spinDraft)){
            /** Create new draft */
            $data = [
                'draft_id' => NULL,
                'spin_id' => $spinId,
                'data' => json_encode([]),
                'segment_deleted' => json_encode([$segmentId]),
                'updated_at' => $this->timezone->convertConfigTimeToUtc($this->timezone->date())
            ];
            $this->_conn->insert('spintowin_draft', $data);
        }else{
            /** Update deleted segment ID */
            $deletedSegment = json_decode((string)$spinDraft['segment_deleted'], true);
            $deletedSegment[] = $segmentId;
            $newDeletedSegment = json_encode($deletedSegment);
            $updateData = [
                'segment_deleted' => $newDeletedSegment,
                'updated_at' => $this->timezone->convertConfigTimeToUtc($this->timezone->date())
            ];
            $this->_conn->update('spintowin_draft', $updateData, 'spin_id='.$spinId);
        }

    }

    public function addDeleteSegments($spinId, $segmentIds){
        $spinDraft = $this->getDraft($spinId);

        if(empty($spinDraft)){
            /** Create new draft */
            $data = [
                'draft_id' => NULL,
                'spin_id' => $spinId,
                'data' => json_encode([]),
                'segment_deleted' => json_encode($segmentIds),
                'updated_at' => $this->timezone->convertConfigTimeToUtc($this->timezone->date())
            ];
            $this->_conn->insert('spintowin_draft', $data);
        }else{
            /** Update deleted segment ID */
            $deletedSegment = json_decode((string)$spinDraft['segment_deleted'], true);
            if(!$deletedSegment){
                $deletedSegment = [];
            }
            $deletedSegment = array_merge($deletedSegment, $segmentIds);

            $newDeletedSegment = json_encode($deletedSegment);

            $updateData = [
                'segment_deleted' => $newDeletedSegment,
                'updated_at' => $this->timezone->convertConfigTimeToUtc($this->timezone->date())
            ];
            $this->_conn->update('spintowin_draft', $updateData, 'spin_id='.$spinId);
        }

    }

    public function getDeletedSegment($spinId){
        $draft = $this->getDraft($spinId, true);
        if(empty($draft)){
            return [];
        }
        $deletedSegment = json_decode((string)$draft['segment_deleted'], true);
        if(!$deletedSegment){
            return [];
        }
        return $deletedSegment;
    }

    public function reloadDraft($isReload){
        if($isReload){
            $this->_draft = null;
        }
    }

}