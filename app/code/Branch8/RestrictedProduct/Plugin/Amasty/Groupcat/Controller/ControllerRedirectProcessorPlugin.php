<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Branch8\RestrictedProduct\Plugin\Amasty\Groupcat\Controller;

use Amasty\Groupcat\Model\Rule as RuleModel;
use Branch8\RestrictedProduct\Model\Rule\ForbiddenActionOptionsProvider;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Url\Encoder;
use Magento\Framework\UrlInterface;

class ControllerRedirectProcessorPlugin
{
    /**
     * @var PageHelper
     */
    private $pageHelper;

    /**
     * @var ActionFlag
     */
    private $actionFlag;

    /**
     * @var ResponseInterface
     */
    private $response;

    private Session $session;

    private UrlInterface $url;
    private mixed $urlEncoder;

    private \Magento\Framework\App\Http\Context $httpContext;

    /**
     * @param PageHelper $pageHelper
     * @param ActionFlag $actionFlag
     * @param ResponseInterface $response
     * @param Session $customerSession
     * @param UrlInterface $url
     * @param Encoder $encoder
     * @param \Magento\Framework\App\Http\Context $httpContext
     */
    public function __construct(
        PageHelper        $pageHelper,
        ActionFlag        $actionFlag,
        ResponseInterface $response,
        Session           $customerSession,
        UrlInterface      $url,
        Encoder           $encoder,
        \Magento\Framework\App\Http\Context $httpContext
    )
    {
        $this->urlEncoder = $encoder;
        $this->pageHelper = $pageHelper;
        $this->actionFlag = $actionFlag;
        $this->response = $response;
        $this->session = $customerSession;
        $this->url = $url;
        $this->httpContext = $httpContext;
    }

    /**
     * @param \Amasty\Groupcat\Controller\ControllerRedirectProcessor $subject
     * @param callable $process
     * @param RuleModel $rule
     * @return void
     */
    public function aroundSetRedirect(\Amasty\Groupcat\Controller\ControllerRedirectProcessor $subject, callable $process, RuleModel $rule): void
    {
        if ($rule->getAllowDirectLinks()) {
            return;
        }
        $currentProductUrl = $this->url->getCurrentUrl();
        $query = http_build_query([
            'back_url' => $this->urlEncoder->encode($currentProductUrl),
            'forbidden' => true
        ]);
        $this->actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
        $this->actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_POST_DISPATCH, true);
        $this->response->setStatusCode(\Laminas\Http\Response::STATUS_CODE_401);
        $this->response->setRedirect('404?' . $query);
        $custGroupIds = $rule->getCustomerGroupIds();
        if(count($custGroupIds) && in_array(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID, $custGroupIds)){
            // only for guest customer group
            if(!$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)){
                $url = $this->pageHelper->getPageUrl($rule->getForbiddenPageIdForGuest());
                if ($url) {
                    $this->response->setRedirect($url . '?' . $query);
                    return;
                }
            }
        }
        if ($rule->getForbiddenAction() == ForbiddenActionOptionsProvider::REDIRECT_TO_SPECIFIED_URL) {
            $specifiedUrl = $rule->getForbiddenSpecificedUrl();
            if ($specifiedUrl) {
                $this->response->setRedirect($specifiedUrl . '?' . $query);
            }
        } elseif ($rule->getForbiddenAction() == ForbiddenActionOptionsProvider::REDIRECT_TO_PAGE) {
            $url = $this->pageHelper->getPageUrl($rule->getForbiddenPageId());
            if ($url) {
                $this->response->setRedirect($url . '?' . $query);
            }
        }
    }
}
