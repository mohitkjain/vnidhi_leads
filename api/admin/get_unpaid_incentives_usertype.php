<?php

class unpaid_incentive
{
    public $user_id;
    public $user_name;
    public $position;
    public $year;
    public $month;
    public $user_incentive;
}

$app->get('/api/admin/incentive/unpaid/{usertype}', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    require_once '../api/settings/config.php';
    
    $usertype = $request->getAttribute('usertype');
    $current_date = date('Y-m-d');
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
                                   WHERE  rd_table.`user_incentive_status` = 'incentive_unpaid' AND rd_table.date <= CURRENT_DATE
                                   GROUP BY rd_table.`user_id`, YEAR(rd_table.`date`), MONTH(rd_table.`date`) 
                                   HAVING rd_table.`user_id` IS NOT NULL
                                   UNION
                                   SELECT fd_table.`user_id` AS 'user_id', YEAR(fd_table.`date`) AS 'year', MONTH(fd_table.`date`) AS 'month', SUM(fd_table.`user_incentive`) AS 'incentive' 
                                   FROM `vn_fd_reward` fd_table
                                   WHERE fd_table.`user_incentive_status` = 'incentive_unpaid' AND fd_table.date <= CURRENT_DATE
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
                                   WHERE  rd_table.`tl_incentive_status` = 'incentive_unpaid' AND rd_table.date <= CURRENT_DATE
                                   GROUP BY rd_table.`tl_id`, YEAR(rd_table.`date`), MONTH(rd_table.`date`) 
                                   HAVING rd_table.`tl_id` IS NOT NULL
                                   UNION
                                   SELECT fd_table.`tl_id` AS 'user_id', YEAR(fd_table.`date`) AS 'year', MONTH(fd_table.`date`) AS 'month', SUM(fd_table.`tl_incentive`) AS 'incentive' 
                                   FROM `vn_fd_reward` fd_table
                                   WHERE fd_table.`tl_incentive_status` = 'incentive_unpaid' AND fd_table.date <= CURRENT_DATE
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
                                WHERE  rd_table.`head_incentive_status` = 'incentive_unpaid' AND rd_table.date <= CURRENT_DATE
                                GROUP BY rd_table.`head_id`, YEAR(rd_table.`date`), MONTH(rd_table.`date`) 
                                HAVING rd_table.`head_id` IS NOT NULL
                                UNION
                                SELECT fd_table.`head_id` AS 'user_id', YEAR(fd_table.`date`) AS 'year', MONTH(fd_table.`date`) AS 'month', SUM(fd_table.`head_incentive`) AS 'incentive' 
                                FROM `vn_fd_reward` fd_table
                                WHERE fd_table.`head_incentive_status` = 'incentive_unpaid' AND fd_table.date <= CURRENT_DATE
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
                WHERE `telecaller_incentive_status` = 'incentive_unpaid' AND `date` <= CURRENT_DATE
                GROUP BY user_id, year, month
                ORDER BY year, month";
            }
            
            $stmt_incentive = $con->prepare($sql_incentive);

            if ($stmt_incentive->execute()) 
            {
                $incentive_data = $stmt_incentive->fetchAll(PDO::FETCH_CLASS, "unpaid_incentive");                          
                if(isset($incentive_data)) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($incentive_data));
                } 
                else 
                { 
                    throw new PDOException('No Records Found.');
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