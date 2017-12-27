<?php

require_once '../settings/config.php';

$config = new config();
$url = $config->set_target_curl();
$ch = curl_init();

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, 0);// No header in the result

// Fetch and return content, save it.
$output_data= curl_exec($ch);
curl_close($ch);

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

// More headers
$headers .= 'From: <mohitjain@techradius.net>' . "\r\n";

$to = "mohitjain@techradius.net";
$subject = "Record Updated";
$message = json_decode($output_data);
mail($to, $subject, $message,  $headers);
?>