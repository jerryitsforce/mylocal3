import React, { useState, useEffect } from "react";
import MenuItem from "./MenuItem";

interface MegaMenuProps {
    dataUrl: string;
}

const MegaMenu: React.FC<MegaMenuProps> = ({ dataUrl }) => {
    const [menuData, setMenuData] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        fetch(dataUrl)
            .then((response) => response.json())
            .then((result) => {
                if (result.success) {
                    setMenuData(result.data);
                } else {
                    setError(result.message || "Failed to load menu data");
                }
                setLoading(false);
            })
            .catch((err) => {
                setError(err.message || "An error occurred");
                setLoading(false);
            });
    }, [dataUrl]);

    if (loading) {
        return <div className="megamenu-loading">Loading menu...</div>;
    }

    if (error) {
        return <div className="megamenu-error">{error}</div>;
    }

    return (
        <nav className="navigation" role="navigation">
            <ul id="mainMenu" className="nav nav-main nav-main-menu">
                {menuData.map((item) => (
                    <MenuItem key={item.id} item={item} />
                ))}
            </ul>
        </nav>
    );
};

export default MegaMenu;
