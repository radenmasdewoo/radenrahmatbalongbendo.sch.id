<?php
header('Content-Type: text/plain');
require_once 'config.php';

function makeRequest($url)
{
    echo "Querying: $url\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch) . "\n";
    }
    curl_close($ch);
    return $response;
}

$pageToken = "EAAcrrDZBjwRkBQ90miwalRQYFiuEV27o4o10OPb38xeQa1SRnZBhfxl4ST90CWtzfbTWFPfFwZBkworIR6TsBwUt1KVS21hF68nPUjoZBOK7BY8t1LejucTu3asBD7LRp2ZAvRjuotmH1vlpZAUP80rZAKDdxknKYpRvPMYb98aqTlkAYjv5dWWI1AfI4qqZCLH76nL89UOH6BGzyeeHhSJVda6h34pFUgNAMYkfoK5ZCGbkZD";

echo "--- CHECKING WABA FOR PAGE ---\n";
$waba = makeRequest("https://graph.facebook.com/v20.0/993015473896539/whatsapp_business_accounts?access_token=$pageToken");
echo $waba . "\n\n";

$wabaData = json_decode($waba, true);
if (isset($wabaData['data'][0]['id'])) {
    $wabaId = $wabaData['data'][0]['id'];
    echo "WABA ID: $wabaId\n";
    $phones = makeRequest("https://graph.facebook.com/v20.0/$wabaId/phone_numbers?access_token=$pageToken");
    echo "Phones: $phones\n";
}
?>