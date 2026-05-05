import React, { FC, useState, useEffect } from "react";

const decodeHtml = (str: string) => {
    if (!str) return str;
    const parser = new DOMParser();
    const doc = parser.parseFromString(str, "text/html");
    return doc.documentElement.textContent || "";
};

const getSafeUrl = (url: string) => {
    const decodedUrl = decodeHtml(url);
    if (!decodedUrl) return "#";
    const lowercaseUrl = decodedUrl.toLowerCase().trim();
    if (lowercaseUrl.startsWith("javascript:")) {
        return "#";
    }
    const anchor = document.createElement("a");
    anchor.href = decodedUrl;
    if (anchor.protocol === "http:" || anchor.protocol === "https:" || !decodedUrl.includes("://")) {
        return decodedUrl;
    }
    return "#";
};

interface BrandProps {
   data: any;
   callBackApplyBrandFilter?: () => void;
}

const BrandInfo: FC<BrandProps> = (props: BrandProps) => {
    const {
        data,
        callBackApplyBrandFilter
    } = props;
    useEffect(() => {
        if (typeof callBackApplyBrandFilter === "function") {
            callBackApplyBrandFilter();
        }
    }, []); // run once after first render
    if (!data || !data.items) return null;

    const [activeLang, setActiveLang] = useState<"en" | "ch">("en");

    const letters = data.brandLetters[activeLang];
    const enAlphabet = data.enAlphabet;
    const chAlphabet = data.chAlphabet;
    let enChAlphabet = enAlphabet;
    if (activeLang === "ch") {
        enChAlphabet = chAlphabet;
    }

    return (
        <div className="ambrands-brandlist-widget">
            <div className="ambrands-brandlist-title">
                <span>{decodeHtml(data.title)}</span>
            </div>

            <div className="content">
                {data.isShowFilter &&
                <div className="ambrands-filters-block">
                    {/* Letter Filter */}
                    <div className="ambrands-letters-filter">
                        <div className="ambrands-letters">
                            <button
                                className="ambrands-letter -letter-all -active"
                            >
                                {decodeHtml(data.labelAll)}
                            </button>

                            {data.filterDisplayAll ?
                                (enChAlphabet.map((letter, index) => (
                                    <button key={decodeHtml(letter)}
                                            className={`ambrands-letter filter-letter letter-${decodeHtml(letter)} ${!letters.includes(letter) ? 'disabled' : ''} ${index === 0 ? '-active' : ''}`}
                                    >
                                        <span className="txt">{decodeHtml(letter)}</span>
                                    </button>
                                ))) :
                                (letters.map((letter, index) => (
                                    <button key={decodeHtml(letter)}
                                            className={`ambrands-letter filter-letter letter-${decodeHtml(letter)} ${index === 0 ? '-active' : ''}`}
                                    >
                                    {decodeHtml(letter)}
                                </button>
                                )))
                            }
                        </div>
                        {/* Language Switch */}
                        <div className="ambrands-switch-box">
                            <div className="ambrands-switch-lang">
                                <button
                                    className={activeLang === "en" ? "ambrands-lang-en active" : "ambrands-lang-en"}
                                    onClick={() => { setActiveLang("en"); }}
                                >
                                    <span>{decodeHtml(data.labelLangEn)}</span>
                                </button>
                                <button
                                    className={activeLang === "ch" ? "ambrands-lang-cn active" : "ambrands-lang-cn"}
                                    onClick={() => { setActiveLang("ch"); }}
                                >
                                    <span>{decodeHtml(data.labelLangCh)}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                }

                {/* Brands */}
                {Object.entries(data.items).map(([lang, parentItems]) => (
                    <section className="ambrands-letters-list">
                        {parentItems.map(subItems => (Object.entries(subItems).map(([letter, list]) => (
                        <div className={`ambrands-letter brand-letter ambrands-letter-${lang} letter-${decodeHtml(letter)}`}>
                            <h3 className="ambrands-title">{decodeHtml(letter)}</h3>

                            <ul className="ambrands-content">
                                {list.map(option => (
                                    <li key={decodeHtml(option.label)} className="ambrands-brand-item">
                                        <a href={getSafeUrl(option.url)} className="ambrands-inner">
                                            {data.isShowLogos && (
                                                option.img ? (
                                                    <span className="ambrands-image-block">
                                                        <img
                                                            className="ambrands-image"
                                                            src={decodeHtml(option.img)}
                                                            alt={decodeHtml(option.alt)}
                                                        />
                                                    </span>
                                                ) : (
                                                    <span className="ambrands-image-block">
                                                        <span className="ambrands-empty">
                                                            {decodeHtml(option.label)}
                                                        </span>
                                                    </span>
                                                )
                                            )}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        ))))}
                    </section>
                ))}
            </div>
        </div>
    );
};

export {BrandInfo };
