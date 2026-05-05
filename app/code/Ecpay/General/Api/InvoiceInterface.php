<?php
namespace Ecpay\General\Api;


interface InvoiceInterface {

    /**
     * Get the invoice URL.
     *
     * @param int $orderId
     * @param string $protectCode
     * @param bool $isApi
     * @return []
     */
    public function getInvoiceUrl($orderId, $protectCode, $isApi=true);

    /**
     *
     * @param string $barcode
     * @return []
     */
    public function checkBarcode($barcode);

    /**
     *
     * @param string $loveCode
     * @return []
     */
    public function checkLoveCode($loveCode);

    /**
     *
     * @param string $carrierNumber
     * @return []
     */
    public function checkCitizenDigitalCertificate($carrierNumber);

    /**
     *
     * @param string $unifiedBusinessNo
     * @return []
     */
    public function checkBusinessNumber(string $unifiedBusinessNo);

    /**
     *
     * @param string $orderId
     * @param string $protectCode
     * @return []
     */
    public function createInvoice($orderId, $protectCode);

    /**
     *
     * @param string $orderId
     * @param string $protectCode
     * @return []
     */
    public function invalidInvoice($orderId, $protectCode);

    /**
     *
     * @param string $orderId
     * @param string $protectCode
     * @return []
     */
    public function getInvoiceTag($orderId, $protectCode);

    /**
     *
     * @return []
     */
    public function getInvoiceMainConfig();
}
