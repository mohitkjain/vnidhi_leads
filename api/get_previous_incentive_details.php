<?php

class Previous_Month_Incentive
{
    public $pre_month;
    public $pre_year;
    public $total_incentive;
    public $total_rd_incentive;
    public $total_fd_incentive;
    public $incentive = array();
}

$app->post('/api/incentive/previous', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $user_id = $parsedBody['user_id'];
    $usertype = $parsedBody['usertype'];

    $payment_status = "paid";
    $current_date = date('Y-m-d');
    $pre_month = date("m", strtotime($current_date . " last month"));
    $pre_year = date("Y", strtotime($current_date . " last month"));
    $last_day_of_month  = date('Y-m-t', strtotime($current_date . " last month"));
    $first_day_of_month  = date('Y-m-01', strtotime($current_date . " last month"));
    $total_rd_incentive = 0;
    $total_fd_incentive = 0;
    $total_incentive = 0;
    $total = new Previous_Month_Incentive(); 
    $config = new config();

    setlocale(LC_MONETARY, 'en_IN');

    if(isset($user_id) && isset($usertype))
    {
        try
        {
            $con = connect_db();
            $stmt = "";

            $sql_rd = "";
            $sql_fd = "";
             
            if($usertype === "Salaried")
            {
                $sql_rd = "SELECT incentive.`lead_id`, incentive.`installment_no`, incentive.`user_incentive` AS 'current_incentive', incentive.`date`, leads.c_name, info.amount FROM `vn_rd_reward_incentive` incentive INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id WHERE incentive.user_id = :user_id AND incentive.payment_status = :payment_status AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)";

                $sql_fd = "SELECT incentive.`lead_id`, incentive.`user_incentive` AS 'current_incentive', incentive.`date`, leads.c_name, info.amount FROM `vn_fd_reward` incentive INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id WHERE incentive.user_id = :user_id AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month) AND incentive.`user_incentive` IS NOT NULL";
            }
            else if($usertype === "Teamleader")
            {
                $sql_rd = "SELECT incentive.`lead_id`, incentive.`installment_no`, incentive.`tl_incentive` AS 'current_incentive', incentive.`date`, leads.c_name, info.amount FROM `vn_rd_reward_incentive` incentive INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id WHERE incentive.tl_id = :user_id AND incentive.payment_status = :payment_status AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)";

                $sql_fd = "SELECT incentive.`lead_id`, incentive.`tl_incentive` AS 'current_incentive', incentive.`date`, leads.c_name, info.amount FROM `vn_fd_reward` incentive INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id WHERE incentive.tl_id = :user_id AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month) AND incentive.`tl_incentive` IS NOT NULL";
            }
            else if($usertype === "Head")
            {
                $sql_rd = "SELECT incentive.`lead_id`, incentive.`installment_no`, incentive.`head_incentive` AS 'current_incentive', incentive.`date`, leads.c_name, info.amount FROM `vn_rd_reward_incentive` incentive INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id WHERE incentive.head_id = :user_id AND incentive.payment_status = :payment_status AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)";

                $sql_fd = "SELECT incentive.`lead_id`, incentive.`head_incentive` AS 'current_incentive', incentive.`date`, leads.c_name, info.amount FROM `vn_fd_reward` incentive INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id WHERE incentive.head_id = :user_id AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month) AND incentive.`head_incentive` IS NOT NULL";
            }
            else if($usertype === "Telecaller")
            {
                $sql_rd = "SELECT incentive.`lead_id`, incentive.`telecaller_incentive` AS 'current_incentive', incentive.`date`, leads.c_name, info.amount FROM vn_incentive_earn_telecaller incentive INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id WHERE incentive.`telecaller_id` = :user_id AND incentive.scheme_type = 'RD' AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)";

                $sql_fd = "SELECT incentive.`lead_id`, incentive.`telecaller_incentive` AS 'current_incentive', incentive.`date`, leads.c_name, info.amount FROM vn_incentive_earn_telecaller incentive INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id WHERE incentive.`telecaller_id` = :user_id AND incentive.scheme_type = 'FD' AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month) AND incentive.`telecaller_incentive` IS NOT NULL";
            }
            
            $stmt_rd = $con->prepare($sql_rd);
            
            $stmt_rd->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_rd->bindParam(':first_day_of_month', $first_day_of_month);
            $stmt_rd->bindParam(':last_day_of_month', $last_day_of_month);

            if($usertype === "Salaried" || $usertype === "Head" || $usertype === "Teamleader")
            {
                $stmt_rd->bindParam(':payment_status', $payment_status);
            }

            if ($stmt_rd->execute()) 
            {
                $rd_data = $stmt_rd->fetchAll(PDO::FETCH_ASSOC);
                foreach($rd_data as $rd)
                {
                    $total_rd_incentive += $rd['current_incentive'];
                }

                $stmt_fd = $con->prepare($sql_fd);

                $stmt_fd->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_fd->bindParam(':first_day_of_month', $first_day_of_month);
                $stmt_fd->bindParam(':last_day_of_month', $last_day_of_month);

                if ($stmt_fd->execute()) 
                {
                    $fd_data = $stmt_fd->fetchAll(PDO::FETCH_ASSOC);

                    foreach($fd_data as $fd)
                    {
                        $total_fd_incentive += $fd['current_incentive'];
                    }


                    $ch = curl_init();                    
                    $url = $config->get_pre_month_target_details() ."". $user_id;
    
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_VERBOSE, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HEADER, 0);// No header in the result 
                    
                    // Fetch and return content, save it.
                    $pre_data = curl_exec($ch);
                    curl_close($ch);

                    if(isset($pre_data))
                    {
                        $pre_data = json_decode($pre_data);
                        $pre_month_target = $pre_data->pre_month_target;
                        $pre_month_achieved = $pre_data->pre_month_achieved;

                        if($pre_month_achieved < $pre_month_target)
                        {
                            $total_fd_incentive = 0;
                            $array  = array();
                            $total->incentive['fd_incentive'] = $array;   
                        }
                        else
                        {
                            $total->incentive['fd_incentive'] = $fd_data;
                        }
                    }

                    $total_incentive = $total_fd_incentive + $total_rd_incentive;

                    $total->incentive['rd_incentive'] = $rd_data;
                    $total->total_fd_incentive = money_format('%!i', number_format((float)$total_fd_incentive, 2, '.', ''));
                    $total->total_rd_incentive = money_format('%!i', number_format((float)$total_rd_incentive, 2, '.', ''));
                    $total->total_incentive = money_format('%!i', number_format((float)$total_incentive, 2, '.', ''));
                    $total->pre_month = $pre_month;
                    $total->pre_year = $pre_year;
                }
                if($total) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($total));
                } 
                else 
                { 
                    throw new PDOException('No Incentive available for this user');
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
    }
});