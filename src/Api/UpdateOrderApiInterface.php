<?php

declare(strict_types=1);

namespace Sylius\PayPalPlugin\Api;

use Sylius\Component\Core\Model\PaymentInterface;

interface UpdateOrderApiInterface
{
    public function update(
        string $token,
        string $orderId,
        PaymentInterface $payment,
        string $referenceId,
        string $merchantId
    ): void;

    public function updatePayPalItemData(
        string $token,
        string $orderId,
        string $referenceId,
        array $payPalItemData
    ): void;
}
