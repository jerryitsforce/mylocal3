import { createGlobalStyle } from 'styled-components';
// import mediaDevice from './utility/mediaDevice';
// import { pBase, reset } from './utility/mixin';

const globalStyle = createGlobalStyle`
  :root {
    --primary-font: 

    --white: #fff;
    --black: #000;

    --primary-color: var(--black);
    --secondary-color: var(--white);

    --root-size: 16px;

    // Layout
    --layout-indent: 30px;
    --layout-max-width: 1200px;
    --layout-gutter: 30px;
    --layout-row-gutter: -30px;
  }
`;

export default globalStyle;
