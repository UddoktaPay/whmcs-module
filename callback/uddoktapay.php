<?php

/**
 * UuddoktaPay WHMCS Gateway
 *
 * Copyright (c) 2022 UuddoktaPay
 * Website: https://uddoktapay.com
 * Developer: rtrasel.com
 * 
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Response data
$payload = file_get_contents('php://input');

if (!empty($payload)) {

    // Decode response data
    $data = json_decode($payload);

    // Retrieve data returned in payload
    $success = true;

    $apiKey = trim($gatewayParams['apiKey']);
    $signature = trim($_SERVER['HTTP_RT_UDDOKTAPAY_API_KEY']);

    if ($apiKey !== $signature) {
        $success = false;
    }

    $transactionStatus = $data->status;
    $invoiceId = $data->metadata->invoice_id;
    $transactionId = $data->transaction_id;
    $paymentAmount = $data->amount;

    if ('COMPLETED' !== trim($transactionStatus)) {
        $success = false;
    }

    // Validate that the invoice is valid.
    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

    // Validate that the transaction is valid.
    checkCbTransID($transactionId);

    // Log the raw JSON response from gateway in the gateway module.
    logTransaction($gatewayParams['name'], $payload, $transactionStatus);

    if ($success) {
        addInvoicePayment(
            $invoiceId,
            $transactionId,
            $paymentAmount,
            0,
            $gatewayModuleName
        );
    }
}

echo 'OK';
