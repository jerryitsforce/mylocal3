<?php


namespace Branch8\Wishlist\Block\Customer\Wishlist\Item\Column;


use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\ConfigInterface;

class Cart extends \Magento\Wishlist\Block\Customer\Wishlist\Item\Column\Cart
{
    /**
     * @var View
     */
    private $productView;

    /**
     * @var EncoderInterface|null
     */
    private $urlEncoder;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     * @param ConfigInterface|null $config
     * @param UrlBuilder|null $urlBuilder
     * @param View|null $productView
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        EncoderInterface $urlEncoder,
        array $data = [],
        ?ConfigInterface $config = null,
        ?UrlBuilder $urlBuilder = null,
        ?View $productView = null
    ) {
        $this->productView = $productView ?: ObjectManager::getInstance()->get(View::class);
        $this->urlEncoder = $urlEncoder;
        parent::__construct($context, $httpContext, $data, $config, $urlBuilder);
    }
    /**
     * Get post parameters.
     *
     * @param Product $product
     * @return array
     */
    public function getAddToCartPostParams(Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data' => [
                'product' => $product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode($url),
            ]
        ];
    }
}
