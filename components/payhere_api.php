<?php

const PAYHERE_APP_ID = '4OVzEhyxKYS4JH5FPGLtmz3LJ';
const PAYHERE_APP_SECRET = '4UrAHvnZXhm4pAuBauVDkM8RkrCrALdjB8Qh8Va9ZUkD';

function payhereRequest($url, array $headers, ?array $post = null)
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL extension is not enabled.');
    }

    $ch = curl_init($url);

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ];

    if ($post !== null) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($post);
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $failed = $response === false;

    curl_close($ch);

    if ($failed || $httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException('Could not verify payment with PayHere.');
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        throw new RuntimeException('Invalid PayHere response.');
    }

    return $data;
}

function findVerifiedPayherePayment($orderId, $amount, $currency)
{
    $authorization = base64_encode(
        trim(PAYHERE_APP_ID) . ':' . trim(PAYHERE_APP_SECRET)
    );

    $tokenResponse = payhereRequest(
        'https://sandbox.payhere.lk/merchant/v1/oauth/token',
        [
            'Authorization: Basic ' . $authorization,
            'Content-Type: application/x-www-form-urlencoded',
        ],
        ['grant_type' => 'client_credentials']
    );

    $accessToken = $tokenResponse['access_token'] ?? '';

    if (!is_string($accessToken) || $accessToken === '') {
        throw new RuntimeException('Could not authenticate with PayHere.');
    }

    $response = payhereRequest(
        'https://sandbox.payhere.lk/merchant/v1/payment/search?order_id=' .
        rawurlencode((string) $orderId),
        [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]
    );

    $responseStatus = (int) ($response['status'] ?? -99);

    if ($responseStatus === -1 || $responseStatus === 0) {
        return null;
    }

    if ($responseStatus !== 1 || !is_array($response['data'] ?? null)) {
        throw new RuntimeException('PayHere could not confirm this payment.');
    }

    foreach ($response['data'] as $payment) {
        if (
            (string) ($payment['order_id'] ?? '') === (string) $orderId &&
            ($payment['status'] ?? '') === 'RECEIVED' &&
            strtoupper((string) ($payment['currency'] ?? '')) ===
                strtoupper((string) $currency) &&
            isset($payment['amount']) &&
            is_numeric($payment['amount']) &&
            (int) round((float) $payment['amount'] * 100) ===
                (int) round((float) $amount * 100) &&
            !empty($payment['payment_id'])
        ) {
            return (string) $payment['payment_id'];
        }
    }

    return null;
}