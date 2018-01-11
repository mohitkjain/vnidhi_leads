<?php

class View_Rewards
{
    public $user_id;
    public $user_name;
    public $position;
    public $user_reward;
    public $reedem_points;
    public $avaiable_rewards;
}

$app->get('/api/admin/rewards/view/{usertype}', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    require_once '../api/settings/config.php';
    
    $usertype = $request->getAttribute('usertype');
    $config = new config();

    if(isset($usertype))
    {
        try
        {
            $con = connect_db();
            $sql_rewards= "";
             
            if($usertype === "Salaried")
            {
                $sql_rewards = "SELECT data_table.user_id, CONCAT(users.fname, ' ', users.lname) AS 'user_name', users.position, SUM(IFNULL(reward, 0)) AS 'user_reward', IFNULL(history.`reedem_points`, 0) AS 'reedem_points', (SUM(IFNULL(reward, 0)) - IFNULL(history.`reedem_points`, 0)) AS 'avaiable_rewards'
                                FROM
                                (
                                        SELECT rd_table.`user_id` AS 'user_id', SUM(rd_table.`user_reward`) AS 'reward' 
                                        FROM `vn_rd_reward_incentive` rd_table
                                        WHERE rd_table.payment_status = 'paid'
                                        GROUP BY rd_table.`user_id`
                                        HAVING rd_table.`user_id` IS NOT NULL
                                        UNION
                                        SELECT fd_table.`user_id` AS 'user_id', SUM(fd_table.`user_reward`) AS 'reward' 
                                        FROM `vn_fd_reward` fd_table
                                        WHERE fd_table.reward_paid >= 'paid'
                                        GROUP BY fd_table.`user_id`
                                        HAVING fd_table.`user_id` IS NOT NULL
                                ) data_table
                                INNER JOIN `vn_users` users ON data_table.user_id = users.user_id
                                LEFT OUTER JOIN `vn_reedemption_history` history ON data_table.user_id = history.user_id
                                GROUP BY data_table.user_id";
            }
            else if($usertype === "Teamleader")
            {
                $sql_rewards = "SELECT data_table.user_id, CONCAT(users.fname, ' ', users.lname) AS 'user_name', users.position, SUM(IFNULL(reward, 0)) AS 'user_reward', IFNULL(history.`reedem_points`, 0) AS 'reedem_points', (SUM(IFNULL(reward, 0)) - IFNULL(history.`reedem_points`, 0)) AS 'avaiable_rewards'
                                FROM
                                (
                                        SELECT rd_table.`tl_id` AS 'user_id', SUM(rd_table.`tl_reward`) AS 'reward' 
                                        FROM `vn_rd_reward_incentive` rd_table
                                        WHERE rd_table.payment_status = 'paid'
                                        GROUP BY rd_table.`tl_id`
                                        HAVING rd_table.`tl_id` IS NOT NULL
                                        UNION
                                        SELECT fd_table.`tl_id` AS 'user_id', SUM(fd_table.`tl_reward`) AS 'reward' 
                                        FROM `vn_fd_reward` fd_table
                                        WHERE fd_table.reward_paid >= 'paid'
                                        GROUP BY fd_table.`tl_id`
                                        HAVING fd_table.`tl_id` IS NOT NULL
                                ) data_table
                                INNER JOIN `vn_users` users ON data_table.user_id = users.user_id
                                LEFT OUTER JOIN `vn_reedemption_history` history ON data_table.user_id = history.user_id
                                GROUP BY data_table.user_id";
            }
            else if($usertype === "Head")
            {
                $sql_rewards = "SELECT data_table.user_id, CONCAT(users.fname, ' ', users.lname) AS 'user_name', users.position, SUM(IFNULL(reward, 0)) AS 'user_reward', IFNULL(history.`reedem_points`, 0) AS 'reedem_points', (SUM(IFNULL(reward, 0)) - IFNULL(history.`reedem_points`, 0)) AS 'avaiable_rewards'
                                FROM
                                (
                                        SELECT rd_table.`head_id` AS 'user_id', SUM(rd_table.`head_reward`) AS 'reward' 
                                        FROM `vn_rd_reward_incentive` rd_table
                                        WHERE rd_table.payment_status = 'paid'
                                        GROUP BY rd_table.`head_id`
                                        HAVING rd_table.`head_id` IS NOT NULL
                                        UNION
                                        SELECT fd_table.`head_id` AS 'user_id', SUM(fd_table.`head_reward`) AS 'reward' 
                                        FROM `vn_fd_reward` fd_table
                                        WHERE fd_table.reward_paid >= 'paid'
                                        GROUP BY fd_table.`head_id`
                                        HAVING fd_table.`head_id` IS NOT NULL
                                ) data_table
                                INNER JOIN `vn_users` users ON data_table.user_id = users.user_id
                                LEFT OUTER JOIN `vn_reedemption_history` history ON data_table.user_id = history.user_id
                                GROUP BY data_table.user_id";
            }

            $stmt_rewards = $con->prepare($sql_rewards);
            if ($stmt_rewards->execute()) 
            {
                $rewards_data = $stmt_rewards->fetchAll(PDO::FETCH_CLASS, "View_Rewards");
                    
                if(isset($rewards_data)) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($rewards_data));
                } 
                else 
                { 
                    throw new PDOException('No Rewards available');
                }
            }  
            else 
            { 
                throw new PDOException('No Rewards available');
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