import { default as React } from "react";
import { createRoot } from "react-dom/client";
import BestSellerWidget from "./components/ProductWidget/BestSellerWidget";
import ProductCarouselWidget from "./components/ProductWidget/ProductCarouselWidget";
import ProductGridWidget from "./components/ProductWidget/ProductGridWidget";

interface ProductWidgetData {
    products: any[];
    title: string;
    mode: string;
    showWishlist: boolean;
    showCompare: boolean;
    showCart: boolean;
    viewMoreUrl?: string;
    showViewMore?: boolean;
}

type ProductWidgetMount = {
    data: ProductWidgetData;
    elId: string;
};

declare global {
    interface Window {
        mountProductWidget: typeof mountProductWidget;
    }
}

const mountProductWidget = ({ data, elId }: ProductWidgetMount) => {
    const rootElement = document.getElementById(elId);
    if (!rootElement) return;

    const isCarousel = data.mode.includes("carousel");
    const isBestSeller = data.mode.includes("bestseller");
    const root = createRoot(rootElement);

    if (isBestSeller) {
        root.render(
            <BestSellerWidget
                products={data.products}
                title={data.title}
                mode={data.mode}
                showWishlist={data.showWishlist}
                showCompare={data.showCompare}
                showCart={data.showCart}
                viewMoreUrl={data.viewMoreUrl}
                showViewMore={data.showViewMore}
            />
        );
    } else if (isCarousel) {
        root.render(
            <ProductCarouselWidget
                products={data.products}
                title={data.title}
                mode={data.mode}
                showWishlist={data.showWishlist}
                showCompare={data.showCompare}
                showCart={data.showCart}
            />
        );
    } else {
        root.render(
            <ProductGridWidget
                products={data.products}
                title={data.title}
                mode={data.mode}
                showWishlist={data.showWishlist}
                showCompare={data.showCompare}
                showCart={data.showCart}
            />
        );
    }
};

if (typeof window !== "undefined") {
    window.mountProductWidget = mountProductWidget;
}

export default mountProductWidget;
