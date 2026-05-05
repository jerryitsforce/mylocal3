import { default as React } from "react";
import { createRoot } from "react-dom/client";
import MegaMenuDropdown from "./components/MegaMenu/MegaMenuDropdown";

interface MegaMenuLoaderParams {
    dataUrl: string;
}

declare global {
    interface Window {
        mountMegaMenu: (params: MegaMenuLoaderParams) => void;
    }
}

let isLoaded = false;
let isLoadingData = false;
let currentParams: MegaMenuLoaderParams | null = null;
let retryTimeout: any = null;

const loadData = () => {
    if (isLoaded || isLoadingData || !currentParams) return;

    isLoadingData = true;
    fetch(currentParams.dataUrl)
        .then((response) => response.json())
        .then((result) => {
            isLoadingData = false;
            if (result.success && result.data) {
                const placeholders = document.querySelectorAll(".react-megamenu-dropdown");
                placeholders.forEach((el) => {
                    const itemId = el.getAttribute("data-item-id");
                    const itemData = result.data.find((d: any) => String(d.id) === itemId);

                    if (itemData) {
                        const root = createRoot(el);
                        root.render(<MegaMenuDropdown item={itemData} />);
                    }
                });
                isLoaded = true;
                if (retryTimeout) {
                    clearTimeout(retryTimeout);
                    retryTimeout = null;
                }
            }
        })
        .catch((err) => {
            isLoadingData = false;
            console.error("Failed to load MegaMenu data:", err);
            // Retry after 10 seconds if it fails (in case the server is temporarily down)
            if (!retryTimeout) {
                retryTimeout = setTimeout(loadData, 10000);
            }
        });
};

const mountMegaMenu = (params: MegaMenuLoaderParams) => {
    currentParams = params;
    loadData();
};

if (typeof window !== "undefined") {
    window.mountMegaMenu = mountMegaMenu;

    // Retry when connection is restored
    window.addEventListener("online", () => {
        if (!isLoaded) {
            console.log("Network back online, retrying MegaMenu load...");
            if (retryTimeout) clearTimeout(retryTimeout);
            retryTimeout = null;
            loadData();
        }
    });
}

export default mountMegaMenu;
