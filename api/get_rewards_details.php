<?php

class Total_Rewards
{
    public $total_reward;
    public $total_rd_reward;
    public $total_fd_reward;
    public $total_reedem_points;
    public $available_reedem_points;
    public $flag_reedemption;
    public $reward = array();
}

$app->post('/api/rewards', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $user_id = $parsedBody['user_id'];
    $usertype = $parsedBody['usertype'];

    $payment_status = "paid";
    $total_rd_reward = 0;
    $total_fd_reward = 0;
    $total_reward = 0;
    $total = new Total_Rewards();

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
                $sql_rd = "SELECT reward.`lead_id`, reward.`installment_no`, reward.`user_reward` AS 'current_reward', reward.`date`, leads.c_name, info.amount FROM `vn_rd_reward_incentive` reward INNER JOIN vn_leads leads ON reward.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON reward.`lead_id` = info.lead_id WHERE reward.user_id = :user_id AND reward.payment_status = :payment_status";

                $sql_fd = "SELECT reward.`lead_id`, reward.`user_reward` AS 'current_reward', reward.`date`, leads.c_name, info.amount FROM `vn_fd_reward` reward INNER JOIN vn_leads leads ON reward.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON reward.`lead_id` = info.lead_id WHERE reward.user_id = :user_id AND reward.`user_reward` IS NOT NULL";
            }
            else if($usertype === "Teamleader")
            {
                $sql_rd = "SELECT reward.`lead_id`, reward.`installment_no`, reward.`tl_reward` AS 'current_reward', reward.`date`, leads.c_name, info.amount FROM `vn_rd_reward_incentive` reward INNER JOIN vn_leads leads ON reward.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON reward.`lead_id` = info.lead_id WHERE reward.tl_id = :user_id AND reward.payment_status = :payment_status";

                $sql_fd = "SELECT reward.`lead_id`, reward.`tl_reward` AS 'current_reward', reward.`date`, leads.c_name, info.amount FROM `vn_fd_reward` reward INNER JOIN vn_leads leads ON reward.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON reward.`lead_id` = info.lead_id WHERE reward.tl_id = :user_id AND reward.`tl_reward` IS NOT NULL";
            }
            else if($usertype === "Head")
            {
                $sql_rd = "SELECT reward.`lead_id`, reward.`installment_no`, reward.`head_reward` AS 'current_reward', reward.`date`, leads.c_name, info.amount FROM `vn_rd_reward_incentive` reward INNER JOIN vn_leads leads ON reward.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON reward.`lead_id` = info.lead_id WHERE reward.head_id = :user_id AND reward.payment_status = :payment_status";

                $sql_fd = "SELECT reward.`lead_id`, reward.`head_reward` AS 'current_reward', reward.`date`, leads.c_name, info.amount FROM `vn_fd_reward` reward INNER JOIN vn_leads leads ON reward.`lead_id` = leads.lead_id INNER JOIN vn_lead_info info ON reward.`lead_id` = info.lead_id WHERE reward.head_id = :user_id AND reward.`head_reward` IS NOT NULL";
            }
            
            $stmt_rd = $con->prepare($sql_rd);
            
            $stmt_rd->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_rd->bindParam(':payment_status', $payment_status);

            if ($stmt_rd->execute()) 
            {
                $rd_data = $stmt_rd->fetchAll(PDO::FETCH_ASSOC);
                foreach($rd_data as $rd)
                {
                    $total_rd_reward += $rd['current_reward'];
                }

                $stmt_fd = $con->prepare($sql_fd);

                $stmt_fd->bindParam(':user_id', $user_id, PDO::PARAM_INT);

                if ($stmt_fd->execute()) 
                {
                    $fd_data = $stmt_fd->fetchAll(PDO::FETCH_ASSOC);

                    foreach($fd_data as $fd)
                    {
                        $total_fd_reward += $fd['current_reward'];
                    }
                    $total_reward = $total_fd_reward + $total_rd_reward;

                    $reedem_sql = "SELECT SUM(history.reedem_points) AS 'total_reedem_points', (SELECT MIN(reedem.rewards_points) FROM `vn_reedemption_data` AS reedem) AS 'min_reedem_points' FROM `vn_reedemption_history` AS history WHERE user_id = :user_id";
                    $stmt_reedem = $con->prepare($reedem_sql);
                    
                    $stmt_reedem->bindParam(':user_id', $user_id, PDO::PARAM_INT);

                    if ($stmt_reedem->execute()) 
                    {
                        $reedem_data = $stmt_reedem->fetch(PDO::FETCH_ASSOC);
                        $total_reedem_points; $flag_reedemption;
                        if(!isset($reedem_data['total_reedem_points']))
                        {
                            $total_reedem_points = 0;
                        }
                        else
                        {
                            $total_reedem_points = $reedem_data['total_reedem_points'];
                        }
                        $min_reedem_points = $reedem_data['min_reedem_points'];

                        $available_reedem_points =  $total_reward -  $total_reedem_points;

                        if($available_reedem_points < $min_reedem_points)
                        {
                            $flag_reedemption = false;
                        }
                        else
                        {
                            $flag_reedemption = true;
                        }

                        $total->reward['rd_reward'] = $rd_data;
                        $total->reward['fd_reward'] = $fd_data;
                        $total->total_fd_reward = $total_fd_reward;
                        $total->total_rd_reward = $total_rd_reward;
                        $total->total_reward = $total_reward;
                        $total->total_reedem_points = $total_reedem_points;
                        $total->available_reedem_points = $available_reedem_points;
                        $total->flag_reedemption = $flag_reedemption;
                    }
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