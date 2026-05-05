import { css } from 'styled-components';
import mediaDevice from './mediaDevice';

/*
 * RESET
 */
const RESET_SETTING = css`
  -webkit-appearance: none;
  font: inherit;

  border: none;
  border-radius: 0;
  margin: 0;
  padding: 0;

  width: auto;
  overflow: visible;
  background: transparent;

  outline: none;
  text-align: inherit;
  line-height: normal;
  color: inherit;

  -webkit-font-smoothing: inherit;
  -moz-osx-font-smoothing: inherit;

  &::-moz-focus-inner {
    border: 0;
    padding: 0;
  }
`;

const reset = {
  body: css`
    -webkit-font-smoothing: antialiased;
    -webkit-tap-highlight-color: transparent;
    -webkit-text-size-adjust: none;
    margin: 0;
    padding: 0;
  `,
  button: css`
    ${RESET_SETTING}
    cursor: pointer;
    touch-action: manipulation;
  `,
  input: css`
    ${RESET_SETTING}
  `
};

/*
 * GRID
 */
const commonGutter = css`
  @media ${mediaDevice.lg} {
    padding-left: var(--layout-gutter);
    padding-right: var(--layout-gutter);
  }

  @media ${mediaDevice.md} {
    padding-left: var(--layout-gutter);
    padding-right: var(--layout-gutter);
  }

  @media ${mediaDevice.sm} {
    padding-left: var(--layout-gutter);
    padding-right: var(--layout-gutter);
  }

  @media ${mediaDevice.xs} {
    padding-left: var(--layout-gutter);
    padding-right: var(--layout-gutter);
  }
`;

const commonGutterLeft = css`
  @media ${mediaDevice.lg} {
    padding-left:var(--layout-gutter);
  }

  @media ${mediaDevice.md} {
    padding-left:var(--layout-gutter);
  }

  @media ${mediaDevice.sm} {
    padding-left: var(--layout-gutter);
  }

  @media ${mediaDevice.xs} {
    padding-left:var(--layout-gutter);
`;

const commonGutterRight = css`
  @media ${mediaDevice.lg} {
    padding-right: var(--layout-gutter);
  }

  @media ${mediaDevice.md} {
    padding-right: var(--layout-gutter);
  }

  @media ${mediaDevice.sm} {
    padding-right: var(--layout-gutter);
  }

  @media ${mediaDevice.xs} {
    padding-right: var(--layout-gutter);
  }
`;
const commonGutterTop = css`
  @media ${mediaDevice.lg} {
    padding-top: var(--layout-gutter);
  }

  @media ${mediaDevice.md} {
    padding-top: var(--layout-gutter);
  }

  @media ${mediaDevice.sm} {
    padding-top: var(--layout-gutter);
  }

  @media ${mediaDevice.xs} {
    padding-top: var(--layout-gutter);
  }
`;

const commonGutterBottom = css`
  @media ${mediaDevice.lg} {
    padding-bottom: var(--layout-gutter);
  }

  @media ${mediaDevice.md} {
    padding-bottom: var(--layout-gutter);
  }

  @media ${mediaDevice.sm} {
    padding-bottom: var(--layout-gutter);
  }

  @media ${mediaDevice.xs} {
    padding-bottom: var(--layout-gutter);
  }
`;

const row = css`
  margin-left: var(--layout-row-gutter);
  margin-right: var(--layout-row-gutter);
`;

const column = css`
  padding-left: var(--layout-gutter);
  padding-right: var(--layout-gutter);
`;

const mxWidth = css`
  margin-left: auto;
  margin-right: auto;
  padding-left: var(--layout-indent);
  padding-right: var(--layout-indent);
  max-width: var(--layout-max-width);

  @media ${mediaDevice.sm} {
    padding-left: var(--layout-gutter);
    padding-right: var(--layout-gutter);
  }
`;

const mxFullWidth = css`
  margin-left: auto;
  margin-right: auto;
  max-width: 100%;
`;

const container = css`
  ${mxWidth}
`;

/*
 * FUNCTIONS
 */

// Clearfix
const clearFix = css`
  &:before,
  &:after {
    content: '';
    display: table;
  }

  &:after {
    clear: both;
  }
`;

// ul, ol reset
const resetList = css`
  list-style: none;
  padding: 0;
  margin: 0;
`;

const primaryContainedButton = css`
  min-height: 54px;
  background: var(--yellow);
  border-radius: 7px;
  color: var(--tertiary-color);
  font-size: var(--font-20-size);
  line-height: var(--font-20-lHeight);
  font-weight: 700;
  text-transform: uppercase;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  padding: 5px 22px;
  min-width: 173px;

  &:hover {
    color: var(--tertiary-color);
  }

  @media ${mediaDevice.md} {
    min-height: 63px;
    font-size: var(--font-22-size);
    line-height: var(--font-22-lHeight);
  }

  &:not([disabled]):hover {
  }

  &:disabled {
    opacity: 0.5;
  }
`;

const secondContainedButton = css`
  border: 6px solid var(--white);
  border-radius: 119px;
  min-height: 66px;
  text-transform: none;
  font-size: var(--font-22-size);
  line-height: var(--font-22-lHeight);
  box-shadow: 0px 4px 20px 0px rgba(0, 0, 0, 0.12);

  @media ${mediaDevice.md} {
    min-height: 86px;
    font-size: var(--font-30-size);
    line-height: var(--font-30-lHeight);
  }
`;

export {
  commonGutter,
  commonGutterLeft,
  commonGutterRight,
  commonGutterTop,
  commonGutterBottom,
  mxWidth,
  mxFullWidth,
  container,
  reset,
  clearFix,
  resetList,
  primaryContainedButton,
  secondContainedButton,
  row,
  column,
};
