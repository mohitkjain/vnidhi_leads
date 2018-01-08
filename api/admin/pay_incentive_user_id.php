<?php

class PayIncentive 
{
    public $result;
}

$app->post('/api/admin/pay/incentive', function ($request, $response) 
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
   
    $config = new config();
    $obj = new PayIncentive();
    if(isset($user_id) && isset($usertype) && isset($year) && isset($month))
    {
        try
        {
            $con = connect_db();
            $stmt = "";

            $sql_rd = "";
            $sql_fd = "";
            $incentive_paid_status = 'incentive_paid';
            $incentive_unpaid_status = 'incentive_unpaid';
            $payment_status = 'paid';
             
            if($usertype === "Salaried")
            {
                $sql_rd = "UPDATE `vn_rd_reward_incentive` 
                SET `user_incentive_status` = :incentive_paid_status
                WHERE `payment_status` =  :payment_status
                AND `user_incentive_status` = :incentive_unpaid_status 
                AND `user_id` = :user_id
                AND  (`date` >= :first_day_of_month AND `date` <= :last_day_of_month)
                AND `user_incentive` IS NOT NULL";

                $sql_fd = "UPDATE `vn_fd_reward`
                SET `user_incentive_status` = :incentive_paid_status
                WHERE `user_incentive_status` = :incentive_unpaid_status  
                AND `user_id` = :user_id
                AND  (`date` >= :first_day_of_month AND `date` <= :last_day_of_month)
                AND `user_incentive` IS NOT NULL";
            }
            else if($usertype === "Teamleader")
            {
                $sql_rd = "UPDATE `vn_rd_reward_incentive` 
                SET `tl_incentive_status` = :incentive_paid_status
                WHERE `payment_status` =  :payment_status
                AND `tl_incentive_status` = :incentive_unpaid_status 
                AND `tl_id` = :user_id
                AND  (`date` >= :first_day_of_month AND `date` <= :last_day_of_month)
                AND `tl_incentive` IS NOT NULL";

                $sql_fd = "UPDATE `vn_fd_reward`
                SET `tl_incentive_status` = :incentive_paid_status
                WHERE `tl_incentive_status` = :incentive_unpaid_status  
                AND `tl_id` = :user_id
                AND  (`date` >= :first_day_of_month AND `date` <= :last_day_of_month)
                AND `tl_incentive` IS NOT NULL";
            }
            else if($usertype === "Head")
            {
                $sql_rd = "UPDATE `vn_rd_reward_incentive` 
                SET `head_incentive_status` = :incentive_paid_status
                WHERE `payment_status` =  :payment_status
                AND `head_incentive_status` = :incentive_unpaid_status 
                AND `head_id` = :user_id
                AND  (`date` >= :first_day_of_month AND `date` <= :last_day_of_month)
                AND `head_incentive` IS NOT NULL";

                $sql_fd = "UPDATE `vn_fd_reward`
                SET `head_incentive_status` = :incentive_paid_status
                WHERE `head_incentive_status` = :incentive_unpaid_status  
                AND `head_id` = :user_id
                AND  (`date` >= :first_day_of_month AND `date` <= :last_day_of_month)
                AND `head_incentive` IS NOT NULL";
            }
            else if($usertype === "Telecaller")
            {
                $sql_fd = "UPDATE `vn_incentive_earn_telecaller` 
                SET `telecaller_incentive_status`= :incentive_paid_status
                WHERE `telecaller_incentive_status` = :incentive_unpaid_status
                AND `telecaller_id` = :user_id
                AND `date` >= :first_day_of_month AND `date` <= :last_day_of_month";
            }

            $stmt_fd = $con->prepare($sql_fd);

            $stmt_fd->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_fd->bindParam(':incentive_paid_status', $incentive_paid_status);
            $stmt_fd->bindParam(':incentive_unpaid_status', $incentive_unpaid_status);
            $stmt_fd->bindParam(':first_day_of_month', $first_day_of_month);
            $stmt_fd->bindParam(':last_day_of_month', $last_day_of_month);    

            if ($stmt_fd->execute()) 
            {
                $count_fd = $stmt_fd->rowCount();

                if($usertype === "Salaried" || $usertype === "Teamleader" || $usertype === "Head")
                {
                    $stmt_rd = $con->prepare($sql_rd);
            
                    $stmt_rd->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt_rd->bindParam(':incentive_paid_status', $incentive_paid_status);
                    $stmt_rd->bindParam(':incentive_unpaid_status', $incentive_unpaid_status);
                    $stmt_rd->bindParam(':payment_status', $payment_status);
                    $stmt_rd->bindParam(':first_day_of_month', $first_day_of_month);
                    $stmt_rd->bindParam(':last_day_of_month', $last_day_of_month); 

                    if ($stmt_rd->execute()) 
                    {                
                        $count_rd = $stmt_rd->rowCount();                             
                    }
                }
                               
                if($count_fd > 0 || $count_rd > 0) 
                {
                    $obj->result = "success";
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($obj));
                } 
                else 
                { 
                    throw new PDOException('Can not Update the records for this user');
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