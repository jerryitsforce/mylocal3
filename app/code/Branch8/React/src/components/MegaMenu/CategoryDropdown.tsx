import React from "react";
import HtmlRenderer from "./HtmlRenderer";

interface CategoryDropdownProps {
    item: any;
}

const CategoryDropdown: React.FC<CategoryDropdownProps> = ({ item }) => {
    // Distribute categories into columns like the PHP logic
    const distributeToColumns = (categories: any[], columns: number) => {
        if (!categories || categories.length === 0 || columns <= 0) return [];
        const arrColumn: any[][] = Array.from({ length: columns }, () => []);
        categories.forEach((cat, index) => {
            arrColumn[index % columns].push(cat);
        });
        return arrColumn;
    };

    let columns = item.columns || 1;
    if (columns > 1 && item.left_content && item.left_col !== 0) {
        columns = columns - item.left_col;
    }
    if (columns > 1 && item.right_content && item.right_col !== 0) {
        columns = columns - item.right_col;
    }

    const columnCategories = distributeToColumns(item.categories, columns);
    const colMdClass = 12 / (item.columns || 1);

    const renderCategory = (category: any, level: number) => {
        const hasChildren = category.children && category.children.length > 0;
        let catClass = `megamenu-item level${level}`;
        if (hasChildren) {
            catClass += " dropdown-submenu";
        }
        if (category.is_active) {
            catClass += " active has-active";
        }

        const linkElement = (
            <a
                href={category.url}
                onClick={(e) => {
                    if (category.url === '#') {
                        e.preventDefault();
                    }
                }}
                {...(level === 3 ? { className: "tab-item-name", id: `tab-${category.id}`, "data-id": category.id } : {})}
            >
                {category.logo && (
                    <div className="category-item-megamenu-logo">
                        <img src={category.logo} height="100" width="100" loading="lazy" alt={category.name} />
                    </div>
                )}
                <p title={category.name}>{category.name}</p>
                {category.label && (
                    <span
                        className="label-menu"
                        style={{ backgroundColor: category.label_background, borderColor: category.label_background }}
                    >
                        {category.label}
                    </span>
                )}
            </a>
        );

        const categoryContent = (
            <>
                {level === 3 ? (
                    <div className="menu-title-lv3">
                        {linkElement}
                        <a href={category.url} className="menu-view-link">
                            <span>{category.view_label}</span>
                        </a>
                    </div>
                ) : (
                    linkElement
                )}
                {hasChildren && (
                    level === 1 ? (
                        <div className="dropdown-menu-list">
                            <div className="ul-list">
                                <ul className="dropdown-menu">
                                    {category.children.map((child: any) => renderCategory(child, level + 1))}
                                </ul>
                            </div>
                        </div>
                    ) : (
                        <div className={`dropdown-menu-lv${level}`}>
                            {level === 2 && (
                                <div className="category-top">
                                    <div className="category-name">
                                        <span>{category.name}</span>
                                        <a href={category.url}>{category.view_label}</a>
                                    </div>
                                    <div className="category-tabs-section">
                                        <div className="category-tabs" id={`cate-${category.id}`}></div>
                                    </div>
                                </div>
                            )}
                            <ul className="dropdown-menu">
                                {category.children.map((child: any) => renderCategory(child, level + 1))}
                            </ul>
                        </div>
                    )
                )
                }
                {
                    category.static_content && (
                        <HtmlRenderer
                            className={`category-item-megamenu-static-content ${category.static_content_class}`}
                            html={category.static_content}
                        />
                    )
                }
            </>
        );

        if (level === 1) {
            return (
                <li
                    key={category.id}
                    className={catClass}
                    id={`category-${category.id}`}
                    data-title={category.name}
                >
                    {categoryContent}
                </li>
            );
        }

        return (
            <li key={category.id} className={catClass}>
                {categoryContent}
            </li>
        );
    };

    return (
        <div className="mega-menu-content">
            <div className="row">
                {item.top_content && (
                    <HtmlRenderer className="top_content static-content col-md-12" html={item.top_content} />
                )}

                {item.left_content && item.left_col !== 0 && (
                    <HtmlRenderer
                        className={`left_content static-content col-md-${colMdClass * item.left_col}`}
                        html={item.left_content}
                    />
                )}

                {columnCategories.map((colItems, index) => (
                    <div key={index} className={`col-md-${colMdClass}`}>
                        <ul className="sub-menu">
                            {colItems.map((cat) => renderCategory(cat, 1))}
                        </ul>
                    </div>
                ))}

                {item.right_content && item.right_col !== 0 && (
                    <HtmlRenderer
                        className={`right_content static-content col-md-${colMdClass * item.right_col}`}
                        html={item.right_content}
                    />
                )}

                {item.bottom_content && (
                    <HtmlRenderer className="bottom_content static-content col-md-12" html={item.bottom_content} />
                )}
            </div>
        </div>
    );
};

export default CategoryDropdown;
