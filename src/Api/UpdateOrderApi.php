<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\PayPalPlugin\Api;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\PayPalPlugin\Client\PayPalClientInterface;
use Sylius\PayPalPlugin\Provider\PaymentReferenceNumberProviderInterface;
use Sylius\PayPalPlugin\Provider\PayPalItemDataProviderInterface;

final class UpdateOrderApi implements UpdateOrderApiInterface
{
    /** @var PayPalClientInterface */
    private $client;

    /** @var PaymentReferenceNumberProviderInterface */
    private $paymentReferenceNumberProvider;

    /** @var PayPalItemDataProviderInterface */
    private $payPalItemsDataProvider;

    public function __construct(
        PayPalClientInterface $client,
        PaymentReferenceNumberProviderInterface $paymentReferenceNumberProvider,
        PayPalItemDataProviderInterface $payPalItemsDataProvider
    ) {
        $this->client = $client;
        $this->paymentReferenceNumberProvider = $paymentReferenceNumberProvider;
        $this->payPalItemsDataProvider = $payPalItemsDataProvider;
    }

    public function update(
        string $token,
        string $orderId,
        PaymentInterface $payment,
        string $referenceId,
        string $merchantId
    ): void {
        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        /** @var AddressInterface $address */
        $address = $order->getShippingAddress();

        $payPalItemData = $this->payPalItemsDataProvider->provide($order);

        $data = [
            'reference_id' => $referenceId,
            'invoice_number' => $this->paymentReferenceNumberProvider->provide($payment),
            'amount' => [
                'currency_code' => $order->getCurrencyCode(),
                'value' => (string) ($order->getTotal() / 100),
                'breakdown' => [
                    'shipping' => [
                        'currency_code' => $order->getCurrencyCode(),
                        'value' => (string) ($order->getShippingTotal() / 100),
                    ],
                    'item_total' => [
                        'currency_code' => $order->getCurrencyCode(),
                        'value' => $payPalItemData['total_item_value'],
                    ],
                    'tax_total' => [
                        'currency_code' => $order->getCurrencyCode(),
                        'value' => $payPalItemData['total_tax'],
                    ],
                ],
            ],
            'payee' => [
                'merchant_id' => $merchantId,
            ],
            'shipping' => [
                'name' => ['full_name' => $address->getFullName()],
                'address' => [
                    'address_line_1' => $address->getStreet(),
                    'admin_area_2' => $address->getCity(),
                    'postal_code' => $address->getPostcode(),
                    'country_code' => $address->getCountryCode(),
                ],
            ],
            'soft_descriptor' => 'Sylius PayPal Payment',
            'items' => $payPalItemData['items'],
        ];

        $this->client->patch(
            sprintf('v2/checkout/orders/%s', $orderId),
            $token,
            [
                [
                    'op' => 'replace',
                    'path' => sprintf('/purchase_units/@reference_id==\'%s\'', $referenceId),
                    'value' => $data,
                ],
            ]
        );
    }

    public function updatePayPalItemData(
        string $token,
        string $orderId,
        string $referenceId,
        array $payPalItemData
    ): void {
        $value = [
            'currency_code' => 'USD',
            'value' => '66.93',
            'breakdown' => [
                'shipping' => ['value' => '1.93', 'currency_code' => 'USD'],
                'item_total' => ['value' => '65.00', 'currency_code' => 'USD'],
                'tax_total' => ['value' => '0.00', 'currency_code' => 'USD'],
            ]
        ];

        $response = $this->client->patch(
            sprintf('v2/checkout/orders/%s', $orderId),
            $token,
            [
                [
                    'op' => 'remove',
                    'path' => sprintf('/purchase_units/@reference_id==\'%s\'/amount', $referenceId),
                ],
            ]
        );

        return;
    }
}
