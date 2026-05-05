import React from "react";
import CategoryDropdown from "./CategoryDropdown";
import HtmlRenderer from "./HtmlRenderer";

interface MegaMenuDropdownProps {
    item: any;
}

const MegaMenuDropdown: React.FC<MegaMenuDropdownProps> = ({ item }) => {
    const hasDropdown = (item.type === "category" && item.categories && item.categories.length > 0) ||
        (item.type === "static" && item.static_content) ||
        item.top_content || item.bottom_content || item.left_content || item.right_content;

    if (!hasDropdown) return null;

    return (
        <div className="dropdown-menu-content" id={`mobile-menu-${item.id}-${item.parent_id}`}>
            <ul className="dropdown-menu">
                <li>
                    {item.type === "category" ? (
                        <CategoryDropdown item={item} />
                    ) : (
                        <HtmlRenderer className="static-content" html={item.static_content} />
                    )}
                </li>
            </ul>
        </div>
    );
};

export default MegaMenuDropdown;
