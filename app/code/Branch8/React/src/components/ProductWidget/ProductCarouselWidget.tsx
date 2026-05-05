import React, { useEffect, useRef } from "react";
import ProductItem, { Product } from "./ProductItem";
interface ProductCarouselWidgetProps {
    products: Product[];
    title: string;
    mode: string;
    showWishlist: boolean;
    showCompare: boolean;
    showCart: boolean;
}

const ProductCarouselWidget: React.FC<ProductCarouselWidgetProps> = ({
    products,
    mode,
    showWishlist,
    showCompare,
    showCart,
}) => {

    return (
        <div
            className={`block widget block-products-list ${mode}`}
            data-content-type="products"
            data-appearance="carousel"
        >
            <ol className="product-items widget-product-carousel">
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
            <div className="swiper-pagination"></div>
            <div className="swiper-button-prev"></div>
            <div className="swiper-button-next"></div>
        </div>
    );
};

export default ProductCarouselWidget;
