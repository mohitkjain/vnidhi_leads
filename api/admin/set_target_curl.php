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
$subject = "FD Target of Current Month";
$targets_data = json_decode($output_data);

if(isset($targets_data))
{
    $target_users_data = array();
    $message = "<html style='-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%'>
                <head>
                <title>Targets For This Month</title>
                </head>
                <body style='font-size: 14px; line-height: 20px; font-weight: 400; color: #3b3b3b;'>
                <div style='margin: 0 auto;padding: 40px; max-width: 800px;'>
                <h2 style='font-size:30px'>Targets For Current Month</h2>
                <table style='margin: 0 0 40px 0; width: 100%; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2); display: table;'>
                    <tr style='display: table-row; background: #f6f6f6;  font-weight: 900; color: #ffffff; background: #2980b9;'>
                        <th>User Name</th>
                        <th>User Type</th>
                        <th>Target</th>
                        <th>Date</th>
                        <th>Result</th>
                    </tr>";
    foreach($targets_data as $target_data)
    {     
        $message .=  "<tr style='display: table-row; background: #f6f6f6;'>
                        <td>". $target_data->name ."</td>
                        <td>". $target_data->usertype ."</td>
                        <td>". $target_data->target ."</td>
                        <td>". $target_data->month_year ."</td>
                        <td>". $target_data->result ."</td>
                    </tr>";
    }                
    $message .= "</table></div>
                </body>
                </html> ";
    mail($to, $subject, $message,  $headers);
 }
?>