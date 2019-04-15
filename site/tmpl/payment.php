<?php

defined('_JEXEC') or die('Restricted access');

$baseUrl = 'https://api.ravepay.co';
$secretKey = $rave->live_sk;
$publicKey = $rave->live_pk;
if ($rave->staging_account == 1) {
  $secretKey = $rave->test_sk;
  $publicKey = $rave->test_pk;
}

switch (strtoupper($rave->currency)) {
  case 'KES':
    $country = 'KE';
    break;
  case 'GHS':
    $country = 'GH';
    break;
  case 'ZAR':
    $country = 'ZA';
    break;
  case 'TZS':
    $country = 'TZ';
    break;
  
  default:
    $country = 'NG';
    break;
}

$metaname = $rave->metaname;
$metavalue = $rave->metavalue;

$metaData = array(['metaname' => $metaname, 'metavalue' => $metavalue]);

$postfields = array();
$postfields['PBFPubKey'] = $publicKey;
$postfields['customer_email'] = $rave->email;
$postfields['customer_firstname'] = $rave->firstname;
$postfields['custom_logo'] = $rave->logo;
$postfields['custom_title'] = $rave->title;
$postfields['customer_lastname'] = $rave->lastname;
$postfields['custom_description'] = $rave->desc; //"Pay for your order on " . $rave->business_name;
$postfields['customer_phone'] = $rave->phone;
$postfields['country'] = $country;
$postfields['txref'] = $rave->txref;
$postfields['payment_method'] = $rave->payment_method;
$postfields['amount'] = $rave->amount + 0;
$postfields['currency'] = strtoupper($rave->currency);
//if ($rave->payment_form === 'hosted') {
  $postfields['redirect_url'] = $rave->redirect_url;
  $postfields['hosted_payment'] = 1;
//}
ksort($postfields);
$stringToHash = "";
foreach ($postfields as $key => $val) {
  $stringToHash .= $val;
}

$stringToHash .= $secretKey;
$hashedValue = hash('sha256', $stringToHash);
$transactionData = array_merge($postfields, array('integrity_hash' => $hashedValue), array('meta' => $metaData));
$json = json_encode($transactionData);

$datas = "";

foreach ($transactionData as $key => $value) {
  $datas .= $key . ": '" . $value . "',";
}

// if ($rave->payment_form === 'hosted') {
  $html = "
      <script type='text/javascript' src='" . $baseUrl . "/flwv3-pug/getpaidx/api/flwpbf-inline.js'></script>
      <script>
      document.addEventListener('DOMContentLoaded', function(event) {
      var data = JSON.parse('" . json_encode($transactionData = array_merge($postfields, array('integrity_hash' => $hashedValue), array('meta' => $metaData))) . "');
      getpaidSetup(data);});
      </script>
      ";
// }
//  else {
//   $html = "
//         <script type='text/javascript' src='" . $baseUrl . "/flwv3-pug/getpaidx/api/flwpbf-inline.js'></script>
//         <script>
//         document.addEventListener('DOMContentLoaded', function(event) {
//         var data = JSON.parse('" . json_encode($transactionData = array_merge($postfields, array('integrity_hash' => $hashedValue))) . "');
//         getpaidSetup({" .
//     $datas
//     . "
//         onclose: function() {
//             window.location = '" . $rave->redirect_url . "?txref=" . $rave->txref . "&cancelled=true';
//         },
//           callback: function(response) {
//             var flw_ref = response.tx.flwRef; // collect flwRef returned and pass to a                  server page to complete status check.
//             console.log('This is the response returned after a charge', response);
//             if (
//               response.tx.chargeResponseCode == '00' ||
//               response.tx.chargeResponseCode == '0'
//             ) {
//               window.location = '" . $rave->redirect_url . "?txref=" . $rave->txref . "';
//             } else {
//               window.location = '" . $rave->redirect_url . "?txref=" . $rave->txref . "';
//             }
//           }
//     });});
//         </script>
//         ";
// }
echo $html;

?>