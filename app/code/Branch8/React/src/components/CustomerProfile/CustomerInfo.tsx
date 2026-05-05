import React, { FC } from "react";
import { CustomerInfoRow, CustomerInfoTitle } from "./Styled";

interface ProfileProps {
   data: any;
}

const CustomerInfo: FC<ProfileProps> = (props: ProfileProps) => {
    const {
        data
    } = props;

    return (
        <CustomerInfoRow>
            <CustomerInfoTitle>{data.name}</CustomerInfoTitle>
            <p>{data.age}</p>
        </CustomerInfoRow>
    );
};

export { CustomerInfo };
