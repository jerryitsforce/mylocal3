<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Address\Edit;

use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddressFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderAddress;

/**
 * Class for common code for buttons on the create/edit address form
 */
class GenericButton
{
    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Address
     */
    private $addressResourceModel;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param ParentOrderAddressFactory $addressFactory
     * @param UrlInterface $urlBuilder
     * @param ParentOrderAddress $addressResourceModel
     * @param RequestInterface $request
     */
    public function __construct(
        ParentOrderAddressFactory $addressFactory,
        UrlInterface              $urlBuilder,
        ParentOrderAddress        $addressResourceModel,
        RequestInterface          $request
    )
    {
        $this->addressFactory = $addressFactory;
        $this->urlBuilder = $urlBuilder;
        $this->addressResourceModel = $addressResourceModel;
        $this->request = $request;
    }

    /**
     * Return address Id.
     *
     * @return int|null
     */
    public function getAddress()
    {
        $address = $this->addressFactory->create();
        $entityId = $this->request->getParam('address_id');
        $this->addressResourceModel->load(
            $address,
            $entityId
        );

        return $address;
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return  string
     */
    public function getUrl($route = '', array $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
