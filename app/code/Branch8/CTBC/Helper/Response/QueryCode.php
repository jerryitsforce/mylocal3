<?php

namespace Branch8\CTBC\Helper\Response;

class QueryCode
{
    const ERR = '-1'; // 傳入參數有誤,請洽客服
    const NONE = '0'; // 找不到符合條件的訂單
    const SUCCESS_ONL = '1'; //查詢成功(唯一一筆資料)
    const SUCCESS_MUL = '2'; //查詢成功(多筆符合條件，僅顯示交易時間最後一筆的授權資料，建議採用 xid 查詢)
}
