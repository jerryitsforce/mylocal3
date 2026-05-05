import { default as React } from "react";
import { createRoot } from "react-dom/client";
import { CustomerProfile } from "components";
import GlobalStyle from 'theme/globalStyle';

type ProfileProps = {
  data: any;
  elCls: string;
};

declare global {
  interface Window {
    CPProfile: typeof CPProfile;
  }
}

const CPProfile = ({ data, elCls }: ProfileProps) => {
  const root = createRoot(
    document.getElementById(elCls) as Element,
  );

  return root.render(
    <>
      <GlobalStyle/>
      <CustomerProfile data={data}/>
    </>
    ,
  );
};

if (typeof window !== "undefined") {
  window.CPProfile = CPProfile;
}

export default CPProfile;