<?php

$app->post('/api/schemes/maturity', function ($request, $response)
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $scheme_id = $parsedBody['scheme_id'];
    $present_value = $parsedBody['present_value'];
    $interest_rate = $parsedBody['int_rate'];
    $num_months = $parsedBody['months'];
    $rate_exist = $parsedBody['rate_exist'];
    $multiple_amount = $parsedBody['multiple_amount'];

    $type = 4; //To calculate quaterly
    $num_year =  (float) ($num_months / 12);
    $interset_details = array();
    $output = array();
    $future_value = "NA";
    $monthly_income = "NA";

    setlocale(LC_MONETARY, 'en_IN');

    try
    {
        if(isset($scheme_id) && isset($present_value) && isset($interest_rate) && isset($num_months) && isset($rate_exist))
        {
            $con = connect_db();

            if($rate_exist == 1)
            {
                if($scheme_id == 7 || $scheme_id == 2)
                {
                    /*
                        M = ( R x (((1 + r)^ n) - 1 )) / (1-(1+r)^-1/3) 
                        M = Maturity value 
                        R = Monthly Installment 
                        r = Rate of Interest (i) / 400 
                        n = Number of Quarters
                    */
                    $future_value = 0;
                    $months = $num_months;
                    $numerator = (1 + ($interest_rate / ($type * 100)));
                    $total_int = 0;
                    $last_balance = 0;
                    
                    for($time = $months, $count = 1, $count_2 = 0; $time > 0; $time--, $count++, $count_2++)
                    {
                        $rd_amount = ($present_value * pow($numerator, ($num_year * $type))); 
                        
                        $future_value += $rd_amount;
                        $interest = round($rd_amount - $present_value, 2);
                        
                        $interset_details[$count_2]['month'] = $count;
                        $interset_details[$count_2]['principal'] = money_format('%!i', number_format((float)$present_value, 2, '.', ''));
                        $interset_details[$count_2]['interest'] = money_format('%!i', number_format((float)$interest, 2, '.', ''));
                        
                        if($count % 3 == 0)
                        {
                            $total_int += $interest;
                            $last_balance += $present_value + $total_int;
                            
                            $interset_details[$count_2]['balance'] = money_format('%!i', number_format((float)$last_balance, 2, '.', ''));
                            $total_int = 0;
                        }
                        else
                        {
                            $last_balance += $present_value;
                            $interset_details[$count_2]['balance'] = money_format('%!i', number_format((float)$last_balance, 2, '.', ''));
                            $total_int += $interest;
                        }
                    
                        $months--;
                        $num_year =  (float) ($months / 12);
                    }
                    /*if($scheme_id == 2)
                    {
                        if($num_months == 24)
                        {
                            $count = $present_value / 1000;
                            $future_value += ($count * 1);
                            
                            $detail = $interset_details[$num_months-1]['interest'] +  ($count * 1);
                            $future_value = round($future_value, 2);
                            
                            $interset_details[$num_months-1]['interest'] = number_format((float)$detail, 2, '.', '');
                            $interset_details[$num_months-1]['balance'] = number_format((float)$future_value, 2, '.', '');
                        }
                        else if($num_months == 36)
                        {
                            $count = $present_value / 1000;
                            $future_value += ($count * 2);
                            
                            $detail = $interset_details[$num_months-1]['interest'] +  ($count * 2);
                            $future_value = round($future_value, 2);
                            
                            $interset_details[$num_months-1]['interest'] = number_format((float)$detail, 2, '.', '');
                            $interset_details[$num_months-1]['balance'] =  number_format((float)$future_value, 2, '.', '');
                        }
                        else if($num_months == 48)
                        {
                            $count = $present_value / 1000;
                            $future_value += ($count * 3);
                            
                            $detail = $interset_details[$num_months-1]['interest'] +  ($count * 3);
                            $future_value = round($future_value, 2);
                            
                            $interset_details[$num_months-1]['interest'] = number_format((float)$detail, 2, '.', '');
                            $interset_details[$num_months-1]['balance'] =  number_format((float)$future_value, 2, '.', '');
                        }
                        else if($num_months == 60)
                        {
                            $count = $present_value / 1000;
                            $future_value += ($count * 5);
                            
                            $detail = $interset_details[$num_months-1]['interest'] +  ($count * 5);
                            $future_value = round($future_value, 2);
                            
                            $interset_details[$num_months-1]['interest'] = number_format((float)$detail, 2, '.', '');
                            $interset_details[$num_months-1]['balance'] = number_format((float)$future_value, 2, '.', '');
                        }
                    }*/
                    $future_value = round($future_value, 0);
                    $output[maturityAmount] = money_format('%!i', number_format((float)$future_value, 2, '.', ''));
                    $output[monthly_report] = $interset_details;
                }
                else if($scheme_id == 3)
                {
                    $dump_Value = ($present_value *  $interest_rate) / 100; 
                    $monthly_income = $dump_Value / 12;
                    $monthly_income = round($monthly_income, 2);
                    $future_value = ($dump_Value * $num_year) + $present_value;
                    $future_value = round($future_value, 0);
                    
                    $output[maturityAmount] = money_format('%!i', number_format((float)$present_value, 2, '.', ''));
                    $output[monthly_income] = money_format('%!i', number_format((float)$monthly_income, 2, '.', ''));
                    
                    $last_balance = $present_value;
                    for($count = 0; $count < $num_months; $count++)
                    {
                        $last_balance += $monthly_income;
                        $interset_details[$count]['month'] = $count + 1;
                        if($count == 0)
                            $interset_details[$count]['principal'] = money_format('%!i', number_format((float)$present_value, 2, '.', ''));
                        else
                            $interset_details[$count]['principal'] = money_format('%!i', number_format((float)0, 2, '.', ''));
                        $interset_details[$count]['interest'] =  money_format('%!i', number_format((float)$monthly_income, 2, '.', ''));
                        $interset_details[$count]['balance'] =  money_format('%!i', number_format((float)$last_balance, 2, '.', ''));
                    }            
                    $output[monthly_report] = $interset_details;
                }
                else if($scheme_id == 1)
                {
                    if($num_months == 12)
                    {
                        $days = 365;
                        $total_present_value = $present_value * $days;
                        $interest = 1882;
                        $count = $present_value / 100;
                        $interest *= $count;
                    }
                    else
                    {
                        $days = 730;
                        $total_present_value = $present_value * $days;
                        $interest = 9490;
                        $count = $present_value / 100;
                        $interest *= $count;
                    }
                    $future_value = $total_present_value + $interest;
                    $output[maturityAmount] = money_format('%!i', number_format((float)$future_value, 2, '.', ''));
                }
                else
                {     
                    //A = P*(1+R/N)^Nt
                    $future_value = floor($present_value * pow(1 + ($interest_rate / ($type * 100)), ($num_year * $type))); 
                    $output[maturityAmount] = money_format('%!i', number_format((float)$future_value, 2, '.', ''));
                    
                    $dump_Value = $present_value;
                    $ys = 3 / 12;
                    
                    $numerator = pow(1 + ($interest_rate / ($type * 100)), ($ys * $type));
                    
                    $print_month = 0;
                    $last_balance = $present_value;
                    
                    
                    for($count = 3; $count <= $num_months; $count+=3)
                    {
                        $dump_Value_2 = round($last_balance * $numerator, 2);
                        $dump_Value = round($dump_Value_2 - $last_balance, 2); 
                        $interest = round(($dump_Value / 3), 2);
                        
                        for($ct = 1; $ct <= 3; $ct++)
                        {
                            $interset_details[$print_month]['month'] = $print_month + 1;
                            if($print_month == 0)
                                $interset_details[$print_month]['principal'] =  money_format('%!i', number_format((float)$present_value, 2, '.', ''));
                            else
                                $interset_details[$print_month]['principal'] =  money_format('%!i', number_format((float)0, 2, '.', ''));
                        
                            $interset_details[$print_month]['interest'] = money_format('%!i', number_format((float)$interest, 2, '.', '')) ;
                            $interset_details[$print_month]['balance'] =  money_format('%!i', number_format((float)$last_balance, 2, '.', ''));
                            $print_month++;
                        }
                        $last_balance += $dump_Value; 
                        $interset_details[--$print_month]['balance'] = money_format('%!i', number_format((float)$last_balance, 2, '.', ''));
                        $print_month++;
                    }
                    $output[monthly_report] = $interset_details;
                }
            }
            else if($rate_exist == 0)
            {
                $amount = $parsedBody['amount'];
                if(isset($multiple_amount) && isset($amount))
                {
                    if($scheme_id == 8)
                    {
                        $future_value = $present_value * 1.5;
                    }
                    else
                    {
                        $present_value = $present_value / $multiple_amount;
                        $future_value = $present_value * $amount;
                    }
                    $output[maturityAmount] = money_format('%!i', number_format((float)$future_value, 2, '.', ''));
                }
            }
            if($output) 
            {
                return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($output));
            } 
            else 
            { 
                throw new PDOException('No records found');
            }
        }                   
    }
    catch(PDOException $e)
    {
        $errors = array();
        $errors[0]['result'] = "failure";
        $errors[0]['error_msg'] = $e->getMessage();
        return $response->withStatus(404)
        ->withHeader('Content-Type', 'application/json')
        ->write(json_encode($errors));
    }
});
