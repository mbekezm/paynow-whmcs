<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/paynowzw/lib/PaynowClient.php';
require_once __DIR__ . '/paynowzw/lib/InitResponse.php';
require_once __DIR__ . '/paynowzw/lib/StatusResponse.php';

function paynowzw_MetaData()
{
    return [
        'DisplayName' => 'Paynow Zimbabwe',
        'APIVersion' => '1.1',
    ];
}

function paynowzw_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Paynow Zimbabwe',
        ],
        'integrationId' => [
            'FriendlyName' => 'Integration ID',
            'Type' => 'text',
            'Size' => '20',
            'Description' => 'Your Paynow integration ID',
        ],
        'integrationKey' => [
            'FriendlyName' => 'Integration Key',
            'Type' => 'password',
            'Size' => '40',
            'Description' => 'Your Paynow integration key',
        ],
        'testModeEmail' => [
            'FriendlyName' => 'Test Mode Email',
            'Type' => 'text',
            'Size' => '40',
            'Description' => 'Optional. While your Paynow integration is in test mode, Paynow requires the auth email to be your Paynow merchant account email. Enter it here to override the client email. Leave empty in production.',
        ],
    ];
}

function paynowzw_link(array $params)
{
    $integrationId = (string) ($params['integrationId'] ?? '');
    $integrationKey = (string) ($params['integrationKey'] ?? '');
    $testModeEmail = trim((string) ($params['testModeEmail'] ?? ''));

    $reference = (string) $params['invoiceid'];
    $amount = (float) $params['amount'];
    $description = (string) $params['description'];
    $clientEmail = (string) ($params['clientdetails']['email'] ?? '');
    $authEmail = $testModeEmail !== '' ? $testModeEmail : $clientEmail;

    $returnUrl = (string) $params['returnurl'];
    $callbackUrl = rtrim((string) $params['systemurl'], '/') . '/modules/gateways/callback/paynowzw.php';

    $requestContext = [
        'reference' => $reference,
        'amount' => $amount,
        'description' => $description,
        'returnUrl' => $returnUrl,
        'callbackUrl' => $callbackUrl,
    ];

    try {
        $client = new \PaynowZW\PaynowClient($integrationId, $integrationKey, $returnUrl, $callbackUrl);
        $initResponse = $client->initiateTransaction($reference, $amount, $description, $authEmail);
    } catch (\Throwable $e) {
        logModuleCall('paynowzw', 'initiatetransaction', $requestContext, $e->getMessage());

        return '<p>Payment could not be initiated. Please try again or contact support.</p>';
    }

    if ($initResponse->isSuccessful() && $initResponse->browserUrl() !== '') {
        $browserUrl = htmlspecialchars($initResponse->browserUrl(), ENT_QUOTES);

        return '<form method="get" action="' . $browserUrl . '">'
            . '<button type="submit" style="background:#2db6e8;color:#ffffff;border:none;border-radius:4px;padding:12px 24px;cursor:pointer;font-size:16px;">Pay with Paynow</button>'
            . '</form>';
    }

    logModuleCall('paynowzw', 'initiatetransaction', $requestContext, $initResponse->raw());

    return '<p>Payment could not be initiated. Please try again or contact support.</p>';
}
