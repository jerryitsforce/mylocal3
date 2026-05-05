import breakpoint from './breakpoint';

const mediaDevice = {
  mxs: `only screen and (max-width:${breakpoint.media.mxs}px)`,
  max_xs: `only screen and (max-width:${breakpoint.media.xs}px)`,
  xs: `only screen and (min-width:${breakpoint.media.xs}px)`,
  ssm: `only screen and (min-width:${breakpoint.media.msm}px)`,
  msm: `only screen and (max-width:${breakpoint.media.msm}px)`,
  // sm: `only screen and (min-width: ${breakpoint.media.xs}px) and (max-width: ${breakpoint.media.sm}px)`,
  sm: `only screen and (min-width: ${breakpoint.media.sm}px)`,
  md: `only screen and (min-width: ${breakpoint.media.md}px)`,
  lg: `only screen and (min-width: ${breakpoint.media.lg}px)`,
  bg: `only screen and (min-width: ${breakpoint.media.bg}px)`,
  mbg: `only screen and (min-width: ${breakpoint.media.mbg}px)`,
  max_lg: `only screen and (max-width: ${breakpoint.media.lg}px)`
};

export default mediaDevice;
