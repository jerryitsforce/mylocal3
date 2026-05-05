import React, { useEffect, useRef } from "react";

interface HtmlRendererProps {
    html: string;
    className?: string;
}

const HtmlRenderer: React.FC<HtmlRendererProps> = ({ html, className }) => {
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!containerRef.current) return;

        const scripts = containerRef.current.querySelectorAll("script");
        scripts.forEach((oldScript) => {
            const newScript = document.createElement("script");

            Array.from(oldScript.attributes).forEach((attr) => {
                newScript.setAttribute(attr.name, attr.value);
            });

            if (oldScript.innerHTML) {
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            }

            oldScript.parentNode?.replaceChild(newScript, oldScript);
        });
    }, [html]);

    return (
        <div
            ref={containerRef}
            className={className}
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
};

export default HtmlRenderer;
