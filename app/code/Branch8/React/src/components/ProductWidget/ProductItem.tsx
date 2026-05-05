import React from "react";

export interface Product {
    id: string;
    sku: string;
    name: string;
    url: string;
    imageUrl: string;
    priceHtml: string;
    reviewsSummaryHtml: string;
    isSaleable: boolean;
    isAvailable: boolean;
    isPreorder: boolean;
    isOOS: boolean;
    isVip: boolean;
    ga4ItemJson: string;
    addToCartParams: {
        action: string;
        data: {
            [key: string]: string;
        };
    };
    ga4Data: {
        itemListId: string;
        itemListName: string;
        promotionId: string;
        promotionName: string;
    };
    productDetailsHtml: string;
    addToWishlistParams: string;
    addToCompareParams: string;
    labels: {
        addToCart: string;
        inStock: string;
        outOfStock: string;
        addToWishlist: string;
        addToCompare: string;
        preorder: string;
        vip: string;
    };
}

interface ProductItemProps {
    item: Product;
    showWishlist: boolean;
    showCompare: boolean;
    showCart: boolean;
    count?: number;
}

const ProductItem: React.FC<ProductItemProps> = ({
    item,
    showWishlist,
    showCompare,
    showCart,
    count,
}) => {
    return (
        <div className="product-item-info">
            <input
                type="hidden"
                className="ga4-item-json"
                value={item.ga4ItemJson}
            />
            <a
                href={item.url}
                className={`product-item-photo ${item.isOOS
                    ? "product-item-photo-oos"
                    : ""
                    }`}
            >
                {count !== undefined && (
                    <span className="counter">{count}</span>
                )}
                <span
                    dangerouslySetInnerHTML={{
                        __html: item.imageUrl,
                    }}
                />
                {item.isOOS && (
                    <div className="action-stock unavailable">
                        <span>{item.labels.outOfStock}</span>
                    </div>
                )}
            </a>
            <div className="product-item-details">
                <strong className="product-item-name">
                    {item.isVip && (
                        <span className="vip-label">{item.labels.vip}</span>
                    )}
                    {item.isPreorder && (
                        <span className="product-badge preorder">
                            {item.labels.preorder}
                        </span>
                    )}
                    <a
                        title={item.name}
                        href={item.url}
                        className="product-item-link"
                    >
                        {item.name}
                    </a>
                </strong>

                {item.reviewsSummaryHtml && (<div
                    dangerouslySetInnerHTML={{
                        __html: item.reviewsSummaryHtml,
                    }}
                />)}

                <div
                    dangerouslySetInnerHTML={{
                        __html: item.priceHtml,
                    }}
                    className="price-box"
                />
                {item.productDetailsHtml &&  (<div
                    className="product-details-html"
                    dangerouslySetInnerHTML={{
                        __html: item.productDetailsHtml,
                    }}
                />)}

                {(showWishlist || showCompare || showCart) && (
                    <div
                        className={`product-item-inner ${item.isOOS
                            ? "stock-unavailable"
                            : ""
                            }`}
                    >
                        <div className="product-item-actions">
                            {showCart && (
                                <div className="actions-primary">
                                    {item.isSaleable ? (
                                        <form
                                            data-role="tocart-form"
                                            data-product-sku={item.sku}
                                            action={item.addToCartParams.action}
                                            method="post"
                                        >
                                            {Object.keys(item.addToCartParams.data).map((key) => (
                                                <input
                                                    key={key}
                                                    type="hidden"
                                                    name={key}
                                                    value={item.addToCartParams.data[key]}
                                                />
                                            ))}
                                            <input
                                                type="hidden"
                                                name="item_list_id"
                                                value={item.ga4Data.itemListId}
                                            />
                                            <input
                                                type="hidden"
                                                name="item_list_name"
                                                value={
                                                    item.ga4Data.itemListName
                                                }
                                            />
                                            <input
                                                type="hidden"
                                                name="promotion_id"
                                                value={item.ga4Data.promotionId}
                                            />
                                            <input
                                                type="hidden"
                                                name="promotion_name"
                                                value={
                                                    item.ga4Data.promotionName
                                                }
                                            />
                                            <input
                                                type="hidden"
                                                name="form_type"
                                                value="product_card"
                                            />
                                            <button
                                                type="submit"
                                                title={item.labels.addToCart}
                                                className="action tocart primary"
                                            >
                                                <span>
                                                    {item.labels.addToCart}
                                                </span>
                                            </button>
                                        </form>
                                    ) : item.isAvailable ? (
                                        <div className="stock available">
                                            {/* <span>{item.labels.inStock}</span> */}
                                        </div>
                                    ) : (
                                        <div className="stock unavailable">
                                            {/* <span>
                                                {item.labels.outOfStock}
                                            </span> */}
                                        </div>
                                    )}
                                </div>
                            )}

                            {(showWishlist || showCompare) && (
                                <div
                                    className="actions-secondary"
                                    data-role="add-to-links"
                                >
                                    {showWishlist && (
                                        <a
                                            href="#"
                                            data-post={item.addToWishlistParams}
                                            className="action towishlist"
                                            data-action="add-to-wishlist"
                                            title={item.labels.addToWishlist}
                                        >
                                            <span>
                                                {item.labels.addToWishlist}
                                            </span>
                                        </a>
                                    )}
                                    {showCompare && (
                                        <a
                                            href="#"
                                            className="action tocompare"
                                            data-post={item.addToCompareParams}
                                            title={item.labels.addToCompare}
                                        >
                                            <span>
                                                {item.labels.addToCompare}
                                            </span>
                                        </a>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default ProductItem;
