<?php

class EMI_Details
{
    public $emi ="";
    public $tot_interest = "";
    public $emi_details = array();
}

$app->post('/api/loan/emi', function ($request, $response)
{
    require_once 'settings/dbconnect.php';

    setlocale(LC_MONETARY, 'en_IN');

    $parsedBody = $request->getParsedBody();
    $amount = $parsedBody['amount'];
    $rate = $parsedBody['rate'];
    $month = $parsedBody['months'];

    $interest_rate = (float)($rate / 1200);
    
    $numerator = $amount * $interest_rate * pow((1 + $interest_rate),  $month);
    $denominator = pow((1 + $interest_rate),  $month) - 1;
    
    $emi = round($numerator / $denominator, 2);
    $tot_interest =  (($emi * $month) - $amount); 
    $bp = $amount;      //Balance Principal
    
    $emi_details = array();

    for($i = 0; $i < $month; $i++)
    {
        $interest = round(($bp *  $interest_rate), 2);
        $principal = $emi - $interest;
        if($i == $month - 1)
            $bp = round($bp - $principal, 0);
        else
            $bp = round($bp - $principal, 2);
        $emi_details[$i]['month'] = ($i + 1);
        $emi_details[$i]['interest'] = money_format('%!i', number_format((float)$interest, 2, '.', ''));
        $emi_details[$i]['principal'] = money_format('%!i', number_format((float)$principal, 2, '.', ''));
        $emi_details[$i]['balance'] = money_format('%!i', number_format((float)$bp, 2, '.', ''));
    }

    $emi_obj = new EMI_Details();
    $emi_obj->emi = money_format('%!i', number_format((float)$emi, 2, '.', ''));
    $emi_obj->tot_interest = money_format('%!i', number_format((float)$tot_interest, 2, '.', ''));
    $emi_obj->emi_details = $emi_details;

    if($emi_obj) 
    {
        return $response->withStatus(200)
        ->withHeader('Content-Type', 'application/json')
        ->write(json_encode($emi_obj));
    } 
    else 
    { 
        throw new PDOException('Error in Calculation');
    }
});
