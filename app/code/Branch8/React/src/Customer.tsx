import { default as React } from "react";
import { createRoot } from "react-dom/client";
import { CustomerProfile } from "components";

type CustomerProps = {
  data: any;
  elCls: string;
};

declare global {
  interface Window {
    CPCustomer: typeof CPCustomer;
  }
}

const CPCustomer = ({ data, elCls }: CustomerProps) => {
  const root = createRoot(
    document.getElementById(elCls) as Element,
  );

  return root.render(
    <CustomerProfile data={data}/>,
  );
};

if (typeof window !== "undefined") {
  window.CPCustomer = CPCustomer;
}

export default CPCustomer;