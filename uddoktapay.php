<?php

/**
 * UuddoktaPay WHMCS Gateway
 *
 * Copyright (c) 2022 UuddoktaPay
 * Website: https://uddoktapay.com
 * Developer: rtrasel.com
 * 
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function uddoktapay_MetaData()
{
    return array(
        'DisplayName' => 'UddoktaPay Gateway',
        'APIVersion' => '1.0.0',
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function uddoktapay_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'UddoktaPay Gateway',
        ),
        'apiKey' => array(
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '60',
            'Default' => '',
            'Description' => 'Get API key from your panel',
        ),
        'apiUrl' => array(
            'FriendlyName' => 'API URL',
            'Type' => 'text',
            'Size' => '255',
            'Default' => '',
            'Description' => 'Get API URL from your panel',
        )
    );
}


function uddoktapay_link($params)
{

    $response = uddoktapay_payment_url($params);
    if ($response->status) {
        return '<form action="' . $response->payment_url . ' " method="GET">
        <input class="btn btn-primary" type="submit" value="' . $params['langpaynow'] . '" />
        </form>';
    }

    return $response->message;
}

function uddoktapay_payment_url($params)
{
    // UuddoktaPay Gateway Specific Settings
    $api_url = $params['apiUrl'];

    // Gateway Configuration Parameters
    $apiKey = $params['apiKey'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];

    // Client Parameters
    $fullname = $params['clientdetails']['firstname'] . " " . $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];

    // System Parameters
    $systemUrl = $params['systemurl'];
    $moduleName = $params['paymentmethod'];

    $returnUrl = uddoktapay_get_url($systemUrl) . '/viewinvoice.php?id=' . $invoiceId;
    $cancelUrl = uddoktapay_get_url($systemUrl) . '/viewinvoice.php?id=' . $invoiceId;
    $webhookUrl = uddoktapay_get_url($systemUrl) . '/modules/gateways/callback/uddoktapay.php';

    $metaData = [
        'invoice_id' => $invoiceId,
        'description' => $description
    ];

    // Compiled Post from Variables
    $postfields = [
        'amount' => $amount,
        'full_name' => $fullname,
        'email' => $email,
        'metadata' => $metaData,
        'redirect_url' => $returnUrl,
        'cancel_url' => $cancelUrl,
        'webhook_url' => $webhookUrl
    ];

    // Setup request to send json via POST.
    $headers = [];
    $headers[] = "Content-Type: application/json";
    $headers[] = "RT-UDDOKTAPAY-API-KEY: {$apiKey}";

    // Contact UuddoktaPay Gateway and get URL data
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response);
    return $result;
}

function uddoktapay_get_url($input)
{
    $input = trim($input);
    if (!preg_match('#^http(s)?://#', $input)) {
        $input = 'https://' . $input;
    }
    $urlHost = parse_url($input, PHP_URL_HOST);
    $domain = preg_replace('/^www\./', '', $urlHost);
    return 'https://' . $domain;
}
