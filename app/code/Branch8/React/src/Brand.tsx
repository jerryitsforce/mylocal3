import { default as React } from "react";
import { createRoot } from "react-dom/client";
import { Brand } from "components";

type Brands = {
  data: any;
  elCls: string;
  callBackApplyBrandFilter?: () => void;
};

declare global {
  interface Window {
      CPBrand: typeof CPBrand;
  }
}

const CPBrand = ({ data, elCls, callBackApplyBrandFilter }: Brands) => {
  const root = createRoot(
    document.getElementById(elCls) as Element,
  );

  return root.render(
      <Brand data={data} callBackApplyBrandFilter={callBackApplyBrandFilter}/>,
  );
};

if (typeof window !== "undefined") {
  window.CPBrand = CPBrand;
}

export default CPBrand;
