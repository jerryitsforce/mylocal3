<?php

declare(strict_types=1);

namespace HotaiConnected\Cart\Service;

use Branch8\HotaiPay\Helper\Data as HotaiPayHelper;
use Branch8\HotaiPay\Model\CreditCard\Session as CreditCardSession;
use Psr\Log\LoggerInterface;

class CreditCardService
{
    const CO_BRANDED_AFFINITY_CODES = [8686, 8687, 8688, 8689, 8690];

    /**
     * @var HotaiPayHelper
     */
    private HotaiPayHelper $hotaiPayHelper;

    /**
     * @var CreditCardSession
     */
    private CreditCardSession $creditCardSession;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param HotaiPayHelper $hotaiPayHelper
     * @param CreditCardSession $creditCardSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        HotaiPayHelper $hotaiPayHelper,
        CreditCardSession $creditCardSession,
        LoggerInterface $logger
    ) {
        $this->hotaiPayHelper = $hotaiPayHelper;
        $this->creditCardSession = $creditCardSession;
        $this->logger = $logger;
    }

    /**
     * Check if user has hotai_token (logged into HotaiPay).
     *
     * @return bool
     */
    public function hasToken(): bool
    {
        return !empty($this->hotaiPayHelper->getToken());
    }

    /**
     * Get credit card list, formatted for API response.
     *
     * @param bool $excludeExpired
     * @return array{cards: array, default_card_token_id: string|null}
     */
    public function getCardList(bool $excludeExpired = false): array
    {
        $result = [
            'cards' => [],
            'default_card_token_id' => null,
        ];

        $rawData = $this->creditCardSession->getCardList();

        if (!$rawData || !isset($rawData['data']) || !is_array($rawData['data'])) {
            return $result;
        }

        $defaultTokenId = $rawData['DefaultCardTokenId'] ?? null;
        if ($defaultTokenId && $defaultTokenId !== CreditCardSession::EMPTY_DEFAULT_CARD) {
            $result['default_card_token_id'] = (string)$defaultTokenId;
        }

        foreach ($rawData['data'] as $card) {
            if ($excludeExpired && !empty($card['IsExpired'])) {
                continue;
            }

            $result['cards'][] = $this->formatCard($card, $defaultTokenId);
        }

        return $result;
    }

    /**
     * Format a single card from raw HotaiPay API response to clean API structure.
     *
     * @param array $card
     * @param mixed $defaultTokenId
     * @return array
     */
    private function formatCard(array $card, $defaultTokenId): array
    {
        $isDefault = !empty($card['DefaultCard']);
        $affinityCode = isset($card['AffinityCode']) ? (int)$card['AffinityCode'] : 0;

        return [
            'id' => (string)($card['Id'] ?? ''),
            'card_no_mask' => $card['CardNoMask'] ?? '',
            'bank_desc' => $card['BankDesc'] ?? '',
            'bank_name' => $card['bankName'] ?? '',
            'branch_name' => $card['branchName'] ?? '',
            'card_type' => $card['CardType'] ?? '',
            'is_default' => $isDefault,
            'is_expired' => !empty($card['IsExpired']),
            'is_available' => !empty($card['IsAvailable']),
            'is_overwrite' => !empty($card['IsOverwrite']),
            'is_co_branded' => in_array($affinityCode, self::CO_BRANDED_AFFINITY_CODES),
            'affinity_code' => (string)$affinityCode,
            'alias_name' => $card['AliasName'] ?? '',
            'display_label' => $this->buildDisplayLabel($card, $isDefault),
        ];
    }

    /**
     * Build display label matching ConfigProvider format.
     *
     * @param array $card
     * @param bool $isDefault
     * @return string
     */
    private function buildDisplayLabel(array $card, bool $isDefault): string
    {
        $prefix = $isDefault ? 'Default Card　' : 'Sub Card　';
        $cardNo = $card['CardNoMask'] ?? '';
        $bank = $card['BankDesc'] ?? '';

        return $prefix . $cardNo . '　' . $bank;
    }
}
