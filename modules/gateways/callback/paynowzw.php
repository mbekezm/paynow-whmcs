<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';

require_once __DIR__ . '/../paynowzw/lib/PaynowClient.php';
require_once __DIR__ . '/../paynowzw/lib/InitResponse.php';
require_once __DIR__ . '/../paynowzw/lib/StatusResponse.php';

$gatewayModuleName = 'paynowzw';
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die('Module not activated');
}

// The return and result URL constructor arguments are not used for
// verification in the callback context, so a fixed Paynow URL is passed
// for both to satisfy the constructor's non-empty requirement.
$client = new \PaynowZW\PaynowClient(
    $gatewayParams['integrationId'],
    $gatewayParams['integrationKey'],
    'https://www.paynow.co.zw/',
    'https://www.paynow.co.zw/'
);

try {
    $callback = $client->verifyCallback($_POST);

    $invoiceId = checkCbInvoiceID($callback->reference(), $gatewayParams['name']);

    // The poll result is the authoritative status; the POSTed status is
    // advisory only. Poll whenever Paynow gave us a pollurl to check.
    if ($callback->pollUrl() !== '') {
        $status = $client->poll($callback->pollUrl());
    } else {
        $status = $callback;
    }

    checkCbTransID($status->paynowReference());

    logTransaction(
        $gatewayParams['name'],
        ['callback' => $callback->raw(), 'poll' => $status->raw()],
        $status->isSettled() ? 'Success' : 'Pending'
    );

    if ($status->isSettled()) {
        // Empty-string amount applies the invoice's full outstanding
        // balance. This is a deliberate project decision to avoid
        // currency conversion mismatches; do not change it.
        addInvoicePayment($invoiceId, $status->paynowReference(), '', 0, $gatewayModuleName);
    }
} catch (\Throwable $e) {
    logTransaction($gatewayParams['name'], ['error' => $e->getMessage(), 'post' => $_POST], 'Error');
    http_response_code(400);
    die('Callback rejected');
}

echo 'OK';
exit;
