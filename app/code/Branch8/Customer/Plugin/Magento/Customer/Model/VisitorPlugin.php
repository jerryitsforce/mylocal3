<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       01/02/2026
 */

namespace Branch8\Customer\Plugin\Magento\Customer\Model;

use Branch8\Customer\Model\ConfigData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RequestSafetyInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Event\Manager;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\DateTime;
use Psr\Log\LoggerInterface;

class VisitorPlugin extends \Magento\Customer\Model\Visitor
{
    /**
     *
     */
    private const TOPIC_PREFIX = 'visitor.track';
    /**
     * @var DateTime\DateTime
     */
    private \Magento\Framework\Stdlib\DateTime\DateTime $date;
    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var ConfigData
     */
    private ConfigData $configData;
    /**
     * @var RequestSafetyInterface|mixed
     */
    private mixed $requestSafety;
    /**
     * @var Manager
     */
    private Manager $eventManager;

    private $request;

    /**
     * @param PublisherInterface $publisher
     * @param ConfigData $configData
     * @param DateTime\DateTime $date
     * @param ModelContext $context
     * @param LoggerInterface $logger
     * @param Registry $registry
     * @param SessionManagerInterface $session
     * @param Header $httpHeader
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime $dateTime
     * @param IndexerRegistry $indexerRegistry
     * @param RequestInterface $request
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $ignoredUserAgents
     * @param array $ignores
     * @param array $data
     * @param RequestSafetyInterface|null $requestSafety
     */
    public function __construct(
        PublisherInterface                          $publisher,
        ConfigData                                  $configData,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        ModelContext                                $context,
        LoggerInterface                             $logger,
        Registry                                    $registry,
        SessionManagerInterface                     $session,
        Header                                      $httpHeader,
        ScopeConfigInterface                        $scopeConfig,
        DateTime                                    $dateTime,
        IndexerRegistry                             $indexerRegistry,
        RequestInterface                            $request,
        AbstractResource                            $resource = null,
        AbstractDb                                  $resourceCollection = null,
        array                                       $ignoredUserAgents = [],
        array                                       $ignores = [],
        array                                       $data = [],
        RequestSafetyInterface                      $requestSafety = null
    )
    {
        parent::__construct(
            $context,
            $registry,
            $session,
            $httpHeader,
            $scopeConfig,
            $dateTime,
            $indexerRegistry,
            $resource,
            $resourceCollection,
            $ignoredUserAgents,
            $ignores,
            $data,
            $requestSafety
        );
        $this->publisher = $publisher;
        $this->date = $date;
        $this->configData = $configData;
        $this->logger = $logger;
        $this->request = $request;
        $this->requestSafety = $requestSafety ?? ObjectManager::getInstance()->get(RequestSafetyInterface::class);
    }

    /**
     * @param \Magento\Customer\Model\Visitor $subject
     * @param callable $process
     * @param $observer
     * @return $this|\Magento\Customer\Model\Visitor
     */
    public function aroundSaveByRequest(\Magento\Customer\Model\Visitor $subject, callable $process, $observer)
    {
        $useQueue = (bool)$this->configData->getValue('customer/online_customers/track_activity_by_queue');
        if (($this->skipRequestLogging || $this->requestSafety->isSafeMethod() || $this->isModuleIgnored($observer))
            && !$this->sessionIdHasChanged()
        ) {
            return $subject;
        }
        try {
            if ($this->session->getSessionId() && $this->getSessionId() != $this->session->getSessionId()) {
                $subject->setSessionId($this->session->getSessionId());
            }
            if ($subject->getId() && $useQueue) {
                $data = $subject->getData();
                $data['date'] = $this->date->date('Y-m-d H:i:s');
                $this->publisher->publish(self::TOPIC_PREFIX, json_encode($data));
            } else {
                $subject->save();
                $data = $subject->getData();
                $this->eventManager->dispatch('visitor_activity_save', ['visitor' => $subject]);
            }
            $data['request'] = $this->request->getFullActionName();

            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'debuglog')){
                $this->logger->debug('Visitor saved by request:' . json_encode($data));
            }
            $this->session->setVisitorData($subject->getData());
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->_logger->critical($e);
            }
        }
        return $this;
    }

    /**
     * @return bool
     */
    private function sessionIdHasChanged(): bool
    {
        $visitorData = $this->session->getVisitorData();
        $hasChanged = false;
        if (isset($visitorData['session_id'])) {
            $hasChanged = $this->session->getSessionId() !== $visitorData['session_id'];
        }
        return $hasChanged;
    }
}
