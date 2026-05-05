import styled from 'styled-components';
import mediaDevice from '../../theme/utility/mediaDevice';
import {
  mxWidth,
} from '../../theme/utility/mixin';

// CustomerInfo
// ------------------------------
// Path: src\components\CustomerProfile\CustomerInfo.tsx

export const CustomerInfoRow = styled.div`
  ${mxWidth}
`;

export const CustomerInfoTitle = styled.h2`
  color: var(--primary-color);
  font-size: 2rem;
  font-weight: 700;
`;