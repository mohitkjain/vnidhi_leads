<?php

class Total_Incentive_Details
{
    public $current_month;
    public $current_year;
    public $total_incentive;
    public $total_rd_incentive;
    public $total_fd_incentive;
    public $incentive_status;
    public $incentive = array();
}

$app->post('/api/admin/incentive_details', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    require_once '../api/settings/config.php';

    $parsedBody = $request->getParsedBody();
    $user_id = $parsedBody['user_id'];
    $usertype = $parsedBody['usertype'];
    $year = $parsedBody['year'];
    $month = $parsedBody['month'];

    $last_day_of_month  = date("$year-$month-t");
    $first_day_of_month  = date("$year-$month-01");
    $total_rd_incentive = 0;
    $total_fd_incentive = 0;
    $total_incentive = 0;
    $total = new Total_Incentive_Details(); 
    $config = new config();

    setlocale(LC_MONETARY, 'en_IN');
    if(isset($user_id) && isset($usertype) && isset($year) && isset($month))
    {
        try
        {
            $con = connect_db();
            $stmt = "";

            $sql_rd = "";
            $sql_fd = "";
             
            if($usertype === "Salaried")
            {
                $sql_rd = "SELECT incentive.`lead_id`, incentive.`installment_no`, incentive.`user_incentive` AS 'incentive', incentive.`user_incentive_status` AS 'incentive_status', incentive.`date`, leads.c_name, info.amount, info.duration 
                FROM `vn_rd_reward_incentive` incentive
                INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id 
                INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id 
                WHERE incentive.user_id = :user_id
                AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)
                AND incentive.`user_incentive` IS NOT NULL";

                $sql_fd = "SELECT incentive.`lead_id`, incentive.`user_incentive` AS 'incentive', incentive.`date`, incentive.`user_incentive_status` AS 'incentive_status', leads.c_name, info.amount, info.duration, (SELECT `target_amount` FROM `vn_target_fd` WHERE `user_id` = :user_id AND `target_year` = :year AND `target_month` = :month) AS 'target_amount', (SELECT `achieved` FROM `vn_target_fd` WHERE `user_id` = :user_id AND `target_year` = :year AND `target_month` = :month) AS 'target_achieved'
                FROM `vn_fd_reward` incentive 
                INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id 
                INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id 
                WHERE incentive.user_id = :user_id
                AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)
                AND incentive.`user_incentive` IS NOT NULL";
            }
            else if($usertype === "Teamleader")
            {
                $sql_rd = "SELECT incentive.`lead_id`, incentive.`installment_no`, incentive.`tl_incentive` AS 'incentive', incentive.`tl_incentive_status` AS 'incentive_status', incentive.`date`, leads.c_name, info.amount, info.duration
                FROM `vn_rd_reward_incentive` incentive
                INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id 
                INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id 
                WHERE incentive.tl_id = :user_id
                AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)
                AND incentive.`tl_incentive` IS NOT NULL";

                $sql_fd = "SELECT incentive.`lead_id`, incentive.`tl_incentive` AS 'incentive', incentive.`date`, incentive.`tl_incentive_status` AS 'incentive_status', leads.c_name, info.amount, info.duration, (SELECT `target_amount` FROM `vn_target_fd` WHERE `user_id` = :user_id AND `target_year` = :year AND `target_month` = :month) AS 'target_amount', (SELECT `achieved` FROM `vn_target_fd` WHERE `user_id` = :user_id AND `target_year` = :year AND `target_month` = :month) AS 'target_achieved' 
                FROM `vn_fd_reward` incentive 
                INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id 
                INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id 
                WHERE incentive.tl_id = :user_id
                AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)
                AND incentive.`tl_incentive` IS NOT NULL";
            }
            else if($usertype === "Head")
            {
                $sql_rd = "SELECT incentive.`lead_id`, incentive.`installment_no`, incentive.`head_incentive` AS 'incentive', incentive.`head_incentive_status` AS 'incentive_status', incentive.`date`, leads.c_name, info.amount, info.duration 
                FROM `vn_rd_reward_incentive` incentive
                INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id 
                INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id 
                WHERE incentive.head_id = :user_id
                AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)
                AND incentive.`head_incentive` IS NOT NULL";

                $sql_fd = "SELECT incentive.`lead_id`, incentive.`head_incentive` AS 'incentive', incentive.`date`, incentive.`head_incentive_status` AS 'incentive_status', leads.c_name, info.amount, info.duration
                FROM `vn_fd_reward` incentive 
                INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id 
                INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id 
                WHERE incentive.head_id = :user_id
                AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)
                AND incentive.`head_incentive` IS NOT NULL";
            }
            else if($usertype === "Telecaller")
            {
                $sql_rd = "SELECT incentive.`lead_id`, incentive.`telecaller_incentive` AS 'incentive', incentive.`telecaller_incentive_status` AS 'incentive_status', incentive.`date`, leads.c_name, info.amount, info.duration
                FROM vn_incentive_earn_telecaller incentive 
                INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id 
                INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id 
                WHERE incentive.`telecaller_id` = :user_id AND incentive.scheme_type = 'RD' 
                AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)";

                $sql_fd = "SELECT incentive.`lead_id`, incentive.`telecaller_incentive` AS 'incentive', incentive.`telecaller_incentive_status` AS 'incentive_status', incentive.`date`, leads.c_name, info.amount, info.duration 
                FROM vn_incentive_earn_telecaller incentive 
                INNER JOIN vn_leads leads ON incentive.`lead_id` = leads.lead_id 
                INNER JOIN vn_lead_info info ON incentive.`lead_id` = info.lead_id 
                WHERE incentive.`telecaller_id` = :user_id AND incentive.scheme_type = 'FD' 
                AND (incentive.date >= :first_day_of_month AND incentive.date <= :last_day_of_month)";
            }
            
            $stmt_rd = $con->prepare($sql_rd);
            
            $stmt_rd->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_rd->bindParam(':first_day_of_month', $first_day_of_month);
            $stmt_rd->bindParam(':last_day_of_month', $last_day_of_month);           

            if ($stmt_rd->execute()) 
            {
                $rd_data = $stmt_rd->fetchAll(PDO::FETCH_ASSOC);
                foreach($rd_data as $rd)
                {
                    $total_rd_incentive += $rd['incentive'];
                }

                $stmt_fd = $con->prepare($sql_fd);

                $stmt_fd->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_fd->bindParam(':first_day_of_month', $first_day_of_month);
                $stmt_fd->bindParam(':last_day_of_month', $last_day_of_month);
                if($usertype === "Teamleader" || $usertype === "Salaried")
                {
                    $stmt_fd->bindParam(':year', $year);
                    $stmt_fd->bindParam(':month', $month);
                }

                if ($stmt_fd->execute()) 
                {
                    $fd_data = $stmt_fd->fetchAll(PDO::FETCH_ASSOC);

                    foreach($fd_data as $fd)
                    {
                        $total_fd_incentive += $fd['incentive'];
                    }
                    if(count($rd_data) > 0) 
                        $incentive_status = $rd_data[0]['incentive_status'];
                    else
                        $incentive_status = 'NA';

                    if(count($fd_data) > 0)
                    {
                        if($usertype === "Teamleader" || $usertype === "Salaried")
                        {
                            $target_amount = $fd_data[0]['target_amount'];
                            $target_achieved = $fd_data[0]['target_achieved'];
    
                            if($target_achieved < $target_amount)
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
                        else
                        {
                            $total->incentive['fd_incentive'] = $fd_data;
                        }
                    }
                    else
                    {
                        $total->incentive['fd_incentive'] = $fd_data;
                    }
                    $total_incentive = $total_fd_incentive + $total_rd_incentive;

                    $total->incentive['rd_incentive'] = $rd_data;
                    $total->total_fd_incentive = money_format('%!i', number_format((float)$total_fd_incentive, 2, '.', ''));
                    $total->total_rd_incentive = money_format('%!i', number_format((float)$total_rd_incentive, 2, '.', ''));
                    $total->total_incentive = money_format('%!i', number_format((float)$total_incentive, 2, '.', ''));
                    $total->current_month = $month;
                    $total->current_year = $year;
                    $total->incentive_status = $incentive_status;                                      
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