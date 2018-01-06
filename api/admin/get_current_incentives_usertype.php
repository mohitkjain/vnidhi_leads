<?php

$app->get('/api/admin/incentive/view_current/{usertype}', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    require_once '../api/settings/config.php';
    
    $usertype = $request->getAttribute('usertype');
    $current_date = date('Y-m-d');
    $last_day_of_month  = date('Y-m-t');
    $first_day_of_last_month  = date('Y-m-01', strtotime($current_date . " last month"));
    $config = new config();

    setlocale(LC_MONETARY, 'en_IN');
    if(isset($usertype))
    {
        try
        {
            $con = connect_db();
            $sql_incentive = "";
             
            if($usertype === "Salaried")
            {
                $sql_incentive = "SELECT data_table.user_id, CONCAT(users.fname, ' ', users.lname) AS 'user_name', users.position, year, month, SUM(IFNULL(incentive, 0)) AS 'user_incentive'
                FROM
                (
                                   SELECT rd_table.`user_id` AS 'user_id', YEAR(rd_table.`date`) AS 'year', MONTH(rd_table.`date`) AS 'month', SUM(rd_table.`user_incentive`) AS 'incentive' 
                                   FROM `vn_rd_reward_incentive` rd_table
                                   WHERE rd_table.date >= :first_day_of_last_month AND rd_table.date <= :last_day_of_month AND rd_table.payment_status = 'paid'
                                   GROUP BY rd_table.`user_id`, YEAR(rd_table.`date`), MONTH(rd_table.`date`) 
                                   HAVING rd_table.`user_id` IS NOT NULL
                                   UNION
                                   SELECT fd_table.`user_id` AS 'user_id', YEAR(fd_table.`date`) AS 'year', MONTH(fd_table.`date`) AS 'month', SUM(fd_table.`user_incentive`) AS 'incentive' 
                                   FROM `vn_fd_reward` fd_table
                                   WHERE fd_table.date >= :first_day_of_last_month AND fd_table.date <= :last_day_of_month
                                   AND YEAR(fd_table.`date`) IN (SELECT target_year FROM vn_view_target_achieved ) 
                                   AND MONTH(fd_table.`date`) IN (SELECT target_month FROM vn_view_target_achieved ) 
                                   AND fd_table.user_id IN (SELECT user_id FROM vn_view_target_achieved) 
                                   GROUP BY fd_table.`user_id`, YEAR(fd_table.`date`), MONTH(fd_table.`date`) 
                                   HAVING fd_table.`user_id` IS NOT NULL
                ) data_table
                INNER JOIN vn_users users ON data_table.user_id = users.user_id
                GROUP BY data_table.user_id, year, month";
            }
            else if($usertype === "Teamleader")
            {
                $sql_incentive = "SELECT data_table.user_id, CONCAT(users.fname, ' ', users.lname) AS 'user_name', users.position, year, month, SUM(IFNULL(incentive, 0)) AS 'user_incentive'
                FROM
                (
                                   SELECT rd_table.`tl_id` AS 'user_id', YEAR(rd_table.`date`) AS 'year', MONTH(rd_table.`date`) AS 'month', SUM(rd_table.`tl_incentive`) AS 'incentive' 
                                   FROM `vn_rd_reward_incentive` rd_table
                                   WHERE rd_table.date >= :first_day_of_last_month AND rd_table.date <= :last_day_of_month AND rd_table.payment_status = 'paid'
                                   GROUP BY rd_table.`tl_id`, YEAR(rd_table.`date`), MONTH(rd_table.`date`) 
                                   HAVING rd_table.`tl_id` IS NOT NULL
                                   UNION
                                   SELECT fd_table.`tl_id` AS 'user_id', YEAR(fd_table.`date`) AS 'year', MONTH(fd_table.`date`) AS 'month', SUM(fd_table.`tl_incentive`) AS 'incentive' 
                                   FROM `vn_fd_reward` fd_table
                                   WHERE fd_table.date >= :first_day_of_last_month AND fd_table.date <= :last_day_of_month
                                   AND YEAR(fd_table.`date`) IN (SELECT target_year FROM vn_view_target_achieved ) 
                                   AND MONTH(fd_table.`date`) IN (SELECT target_month FROM vn_view_target_achieved ) 
                                   AND fd_table.tl_id IN (SELECT user_id FROM vn_view_target_achieved) 
                                   GROUP BY fd_table.`tl_id`, YEAR(fd_table.`date`), MONTH(fd_table.`date`) 
                                   HAVING fd_table.`tl_id` IS NOT NULL
                ) data_table
                INNER JOIN vn_users users ON data_table.user_id = users.user_id
                GROUP BY data_table.user_id, year, month";
            }
            else if($usertype === "Head")
            {
                $sql_incentive = "SELECT data_table.user_id, CONCAT(users.fname, ' ', users.lname) AS 'user_name', users.position, year, month, SUM(IFNULL(incentive, 0)) AS 'user_incentive'
                FROM
                (
                                SELECT rd_table.`head_id` AS 'user_id', YEAR(rd_table.`date`) AS 'year', MONTH(rd_table.`date`) AS 'month', SUM(rd_table.`head_incentive`) AS 'incentive' 
                                FROM `vn_rd_reward_incentive` rd_table
                                WHERE rd_table.date >= :first_day_of_last_month AND rd_table.date <= :last_day_of_month AND rd_table.payment_status = 'paid'
                                GROUP BY rd_table.`head_id`, YEAR(rd_table.`date`), MONTH(rd_table.`date`) 
                                HAVING rd_table.`head_id` IS NOT NULL
                                UNION
                                SELECT fd_table.`head_id` AS 'user_id', YEAR(fd_table.`date`) AS 'year', MONTH(fd_table.`date`) AS 'month', SUM(fd_table.`head_incentive`) AS 'incentive' 
                                FROM `vn_fd_reward` fd_table
                                WHERE fd_table.date >= :first_day_of_last_month AND fd_table.date <= :last_day_of_month
                                GROUP BY fd_table.`head_id`, YEAR(fd_table.`date`), MONTH(fd_table.`date`) 
                                HAVING fd_table.`head_id` IS NOT NULL
                ) data_table
                INNER JOIN vn_users users ON data_table.user_id = users.user_id
                 GROUP BY data_table.user_id, year, month";
            }
            else if($usertype === "Telecaller")
            {
                $sql_incentive = "SELECT `telecaller_id` AS 'user_id', CONCAT(users.fname, ' ', users.lname) AS 'user_name', users.position, YEAR(`date`) AS 'year', MONTH(`date`) AS 'month' , SUM(IFNULL(`telecaller_incentive`, 0)) AS 'user_incentive' 
                FROM `vn_incentive_earn_telecaller` telecaller 
                INNER JOIN vn_users users ON telecaller.`telecaller_id` = users.user_id 
                WHERE `date` > :first_day_of_last_month AND `date` <= :last_day_of_month 
                GROUP BY user_id, year, month";
            }
            
            $stmt_incentive = $con->prepare($sql_incentive);            
            $stmt_incentive->bindParam(':first_day_of_last_month', $first_day_of_last_month);
            $stmt_incentive->bindParam(':last_day_of_month', $last_day_of_month);

            if ($stmt_incentive->execute()) 
            {
                $incentive_data = $stmt_incentive->fetchAll(PDO::FETCH_ASSOC);
                $result = array();
                $current_month = date('m');
                $pre_month = date('m', strtotime($current_date . " last month"));
                foreach($incentive_data as $user_incentive)
                {
                    $key = $user_incentive['user_id'];
                    if(empty($result[$key]['user_id']))
                    {
                        $result[$key]['user_id'] = $user_incentive['user_id'];
                        $result[$key]['user_name'] = $user_incentive['user_name'];
                        $result[$key]['position'] = $user_incentive['position'];
                    }
                    if($current_month == $user_incentive['month'])
                    {
                        $result[$key]['current_incentive'] = money_format('%!i', $user_incentive['user_incentive']);
                    }
                    else if($pre_month == $user_incentive['month'])
                    {
                        $result[$key]['pre_incentive'] = money_format('%!i', $user_incentive['user_incentive']);
                    }
                    if(!isset($result[$key]['current_incentive']))
                        $result[$key]['current_incentive'] = 0;
                    if(!isset($result[$key]['pre_incentive']))
                        $result[$key]['pre_incentive'] = 0;
                }               
                if(isset($result)) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($result));
                } 
                else 
                { 
                    throw new PDOException('No Incentive available');
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