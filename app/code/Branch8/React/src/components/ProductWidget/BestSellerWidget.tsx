import React from "react";
import ProductItem, { Product } from "./ProductItem";

interface BestSellerWidgetProps {
    products: Product[];
    title: string;
    mode: string;
    showWishlist: boolean;
    showCompare: boolean;
    showCart: boolean;
    viewMoreUrl?: string;
    showViewMore?: boolean;
}

const BestSellerWidget: React.FC<BestSellerWidgetProps> = ({
    products,
    title,
    mode,
    showWishlist,
    showCompare,
    showCart,
    viewMoreUrl,
    showViewMore,
}) => {
    const listTop = products.slice(0, 3);
    const listBottom = products.slice(3);

    return (
        <div className="widget-bestseller-container">
            {title && (
                <h3 className="heading ico-popular" dangerouslySetInnerHTML={{ __html: title }} />
            )}

            <ol className="product-items widget-non-carousel widget-bestseller top-items">
                {listTop.map((item, index) => (
                    <li
                        className="product-item"
                        key={`${item.id}-${index}`}
                        data-item-list-id={item.ga4Data.itemListId}
                        data-item-list-name={item.ga4Data.itemListName}
                        data-promotion-id={item.ga4Data.promotionId}
                        data-promotion-name={item.ga4Data.promotionName}
                    >
                        <ProductItem
                            item={item}
                            showWishlist={showWishlist}
                            showCompare={showCompare}
                            showCart={showCart}
                            count={index + 1}
                        />
                    </li>
                ))}
            </ol>

            {listBottom.length > 0 && (
                <ol className={`product-items widget-bestseller ${mode === 'bestseller_carousel' ? 'widget-product-carousel' : mode}`}>
                    {listBottom.map((item, index) => (
                        <li
                            className="product-item"
                            key={`${item.id}-${index + 3}`}
                            data-item-list-id={item.ga4Data.itemListId}
                            data-item-list-name={item.ga4Data.itemListName}
                            data-promotion-id={item.ga4Data.promotionId}
                            data-promotion-name={item.ga4Data.promotionName}
                        >
                            <ProductItem
                                item={item}
                                showWishlist={showWishlist}
                                showCompare={showCompare}
                                showCart={showCart}
                                count={index + 4}
                            />
                        </li>
                    ))}
                    {showViewMore && viewMoreUrl && (
                        <li className="product-item view-more">
                            <a href={viewMoreUrl} className="action primary">
                                View More
                            </a>
                        </li>
                    )}
                </ol>
            )}
        </div>
    );
};

export default BestSellerWidget;
