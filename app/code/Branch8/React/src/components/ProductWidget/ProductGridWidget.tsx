import React from "react";
import ProductItem, { Product } from "./ProductItem";

interface ProductGridWidgetProps {
    products: Product[];
    title: string;
    mode: string;
    showWishlist: boolean;
    showCompare: boolean;
    showCart: boolean;
}

const ProductGridWidget: React.FC<ProductGridWidgetProps> = ({
    products,
    title,
    mode,
    showWishlist,
    showCompare,
    showCart,
}) => {
    return (
        <div className={`block widget block-products-list ${mode}`}>
            {title && (
                <div className="block-title">
                    <strong>{title}</strong>
                </div>
            )}
            <div className="block-content">
                <div className={`products-${mode} ${mode}`}>
                    <ol className={`product-items widget-product-grid`}>
                        {products.map((item, index) => (
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
                                />
                            </li>
                        ))}
                    </ol>
                </div>
            </div>
        </div>
    );
};

export default ProductGridWidget;
