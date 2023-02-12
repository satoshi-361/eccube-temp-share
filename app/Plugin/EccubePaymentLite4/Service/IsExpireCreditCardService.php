<?php

namespace Plugin\EccubePaymentLite4\Service;

class IsExpireCreditCardService
{
    /**
     * @var GetCardExpireDateTimeService
     */
    private $getCardExpireDateTimeService;

    public function __construct(
        GetCardExpireDateTimeService $getCardExpireDateTimeService
    ) {
        $this->getCardExpireDateTimeService = $getCardExpireDateTimeService;
    }

    public function handle(string $cardExpire): bool
    {
        // 現在日時 > カード有効期限日時 + 1day の場合はカード有効期限切れのため、決済エラーとする。
        $expireDateTime = $this->getCardExpireDateTimeService->get($cardExpire);
        $expireDateTime->modify('+ 1day');
        $today = new \DateTime('now');
        if ($today > $expireDateTime) {
            return true;
        }

        return false;
    }
}
