import React, { useState } from "react";
import CategoryDropdown from "./CategoryDropdown";

interface MenuItemProps {
    item: any;
}

const MenuItem: React.FC<MenuItemProps> = ({ item }) => {
    const [isOpen, setIsOpen] = useState(false);

    const hasDropdown = (item.type === "category" && item.categories && item.categories.length > 0) ||
        (item.type === "static" && item.static_content) ||
        item.top_content || item.bottom_content || item.left_content || item.right_content;

    let itemClass = `${item.class}`;
    if (item.is_active) {
        if (!itemClass.includes('active')) {
            itemClass += " active has-active";
        }
    }
    if (isOpen) {
        itemClass += " open";
    }

    const handleMouseEnter = () => setIsOpen(true);
    const handleMouseLeave = () => setIsOpen(false);

    return (
        <li
            className={itemClass}
            onMouseEnter={handleMouseEnter}
            onMouseLeave={handleMouseLeave}
            data-megamenu-id={item.megamenu_id}
        >
            <a href={item.url} className={`level0 ${hasDropdown ? 'dropdown-toggle' : ''}`}>
                {item.mobile_top_content && (
                    <div className="top_content_mobile static-content col-md-12" dangerouslySetInnerHTML={{ __html: item.mobile_top_content }} />
                )}
                {item.html_label && (
                    <span dangerouslySetInnerHTML={{ __html: item.html_label }} />
                )}
                <span data-hover={item.title}>{item.title}</span>
                {hasDropdown && <span className="icon-next"></span>}
            </a>

            {hasDropdown && (
                <div className="dropdown-menu-content" id={`mobile-menu-${item.id}-${item.parent_id}`}>
                    <ul className="dropdown-menu">
                        <li>
                            {item.type === "category" ? (
                                <CategoryDropdown item={item} />
                            ) : (
                                <div className="static-content" dangerouslySetInnerHTML={{ __html: item.static_content }} />
                            )}
                        </li>
                    </ul>
                </div>
            )}
        </li>
    );
};

export default MenuItem;
