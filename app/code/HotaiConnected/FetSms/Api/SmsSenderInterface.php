<?php
namespace HotaiConnected\FetSms\Api;

interface SmsSenderInterface
{
    /**
     * 發送簡訊
     *
     * @param string $phone 接收手機號碼
     * @param string $content 簡訊內容 (純文字，程式會自動 Base64)
     * @param string $callerName 呼叫者識別 (格式: 模組名_類別名)
     * @return array 回傳 API 回應結果
     */
    public function send(string $phone, string $content, string $callerName): array;
}
