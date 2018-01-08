<?php

class RD_Installment 
{
    public $result = "";
}

$app->post('/api/admin/rd/installment', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    require_once '../api/settings/config.php';

    $parsedBody = $request->getParsedBody();
    $installment_no = $parsedBody['installment_no'];
    $lead_id = $parsedBody['lead_id'];

    $users = array("Salaried", "Teamleader", "Head");
    $config = new config();
    $result;
    $last_day_this_month  = date('Y-m-t');

    if(isset($installment_no) && isset($lead_id))
    {
        try
        {
            $con = connect_db();
            $con->beginTransaction();  
           
            $sql = "SELECT incentive.lead_id, incentive.`user_id`, incentive.`tl_id`, incentive.`head_id`, incentive.`payment_status`, info.lead_type, scheme_desc.scheme_type, info.duration, info.amount, assignee.usertype FROM vn_rd_reward_incentive incentive INNER JOIN `vn_lead_info` AS info ON incentive.lead_id = info.lead_id INNER JOIN vn_users assignee ON info.user_id = assignee.user_id INNER JOIN vn_scheme_description scheme_desc ON info.scheme_id = scheme_desc.scheme_id WHERE incentive.lead_id = :lead_id AND installment_no = :installment_no";

            //Prepare a Query Statement
            $stmt = $con->prepare($sql);           
            
            $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
            $stmt->bindParam(':installment_no', $installment_no, PDO::PARAM_INT);

            //Execute a query statement
            if($stmt->execute()) 
            {
                $rd_data = $stmt->fetch();
                if($rd_data['scheme_type'] === "RD" && $rd_data['payment_status'] === "unpaid")
                {
                    $rewards = array();
                    $percents = array();

                    foreach($users as $user)
                    {
                        $ch = curl_init();
                        $url = $config->getrewards();
                        $post_data = "user_type=".$user."&lead_type=".$rd_data['lead_type'];
                        
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_VERBOSE, 1);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_HEADER, 0);// No header in the result
                        
                        // Fetch and return content, save it.
                        $output_data= curl_exec($ch);
                        curl_close($ch);

                        $rewards["$user"] = json_decode($output_data);

                        foreach($rewards["$user"] as $reward_user)
                        {
                            if(isset($reward_user->reward_per))
                            {
                                $rewards["$user"][$reward_user->year_wise] = $reward_user->reward_per;
                            }
                        }

                        $ch = curl_init();
                        $url = $config->getpercent();
                        $post_data = "scheme_type=".$rd_data['scheme_type']."&user_type=".$user."&lead_type=".$rd_data['lead_type'];
                        
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_VERBOSE, 1);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_HEADER, 0);// No header in the result
                        
                        // Fetch and return content, save it.
                        $output_data= curl_exec($ch);
                        curl_close($ch);

                        $percents["$user"] = json_decode($output_data);

                        foreach($percents["$user"] as $percent_user)
                        {
                            if(isset($percent_user->incentive_per))
                            {
                                $percents["$user"][$percent_user->duration] = $percent_user->incentive_per;
                            }
                        }
                    }

                    $user_reward; $tl_reward; $head_reward;
                    $user_incentive; $tl_incentive; $head_incentive;

                    $installment_no_year = ceil($installment_no / 12);
                    $reward_year;
                    if($installment_no_year == 1)
                    {
                        $reward_year = 1;
                    }
                    else
                    {
                        $reward_year = 2;
                    }

                    if($rd_data['lead_type'] === "company_lead")
                    {                                               
                        $user_reward = (($rd_data['amount'] * $rewards[$users[0]][$reward_year]) / 100);
                        $tl_reward = (($rd_data['amount'] * $rewards[$users[1]][$reward_year]) / 100);
                        $head_reward = (($rd_data['amount'] * $rewards[$users[2]][$reward_year]) / 100);

                        $user_incentive = (($rd_data['amount'] * $percents[$users[0]][$installment_no_year]) / 100);
                        $tl_incentive = (($rd_data['amount'] * $percents[$users[1]][$installment_no_year]) / 100);
                        $head_incentive = (($rd_data['amount'] * $percents[$users[2]][$installment_no_year]) / 100);

                        $paid_status = 'paid';
                        $incentive_status = 'incentive_unpaid';
                        $sql = "UPDATE `vn_rd_reward_incentive` SET `payment_status`= :paid_status, `user_reward`= :user_reward, `user_incentive`= :user_incentive, `user_incentive_status` = :incentive_status, `tl_reward`= :tl_reward, `tl_incentive`= :tl_incentive, `tl_incentive_status` = :incentive_status, `head_reward`= :head_reward,`head_incentive`= :head_incentive, `head_incentive_status` = :incentive_status WHERE `lead_id` = :lead_id AND `installment_no` = :installment_no AND `payment_status` = :unpaid_status AND date <= :last_day_this_month ";

                        //Prepare a Query Statement
                        $stmt = $con->prepare($sql);

                        $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                        $stmt->bindParam(':installment_no', $installment_no, PDO::PARAM_INT);
                        $stmt->bindParam(':paid_status', $paid_status);
                        $stmt->bindParam(':user_reward', $user_reward);
                        $stmt->bindParam(':user_incentive', $user_incentive);
                        $stmt->bindParam(':tl_reward', $tl_reward);
                        $stmt->bindParam(':tl_incentive', $tl_incentive);
                        $stmt->bindParam(':head_reward', $head_reward);
                        $stmt->bindParam(':head_incentive', $head_incentive);
                        $stmt->bindParam(':unpaid_status', $rd_data['payment_status']);
                        $stmt->bindParam(':last_day_this_month', $last_day_this_month);
                        $stmt->bindParam(':incentive_status', $incentive_status);

                        if($stmt->execute())
                        {
                            $count = $stmt->rowCount();
                            if($count == 1) 
                            {
                                $result['result'] = "success";                                
                            }
                            else
                            {
                                throw new PDOException("Can not update the status.");
                            }
                        }
                        else
                        {
                            throw new PDOException("Can not update the status.");
                        }        
                    }
                    else if($rd_data['lead_type'] === "direct_lead")
                    {
                        $stmt = "";
                        if($rd_data['usertype'] === "Teamleader")
                        {
                            $tl_reward = (($rd_data['amount'] * $rewards[$users[1]][$reward_year]) / 100);
    
                            $tl_incentive = (($rd_data['amount'] * $percents[$users[1]][$installment_no_year]) / 100);
    
                            $paid_status = 'paid';
                            $incentive_status = 'incentive_unpaid';
                            $sql = "UPDATE `vn_rd_reward_incentive` SET `payment_status`= :paid_status,  `tl_reward`= :tl_reward, `tl_incentive`= :tl_incentive, `tl_incentive_status` = :incentive_status WHERE `lead_id` = :lead_id AND `installment_no` = :installment_no AND `payment_status` = :unpaid_status AND date <= :last_day_this_month ";
    
                            //Prepare a Query Statement
                            $stmt = $con->prepare($sql);
    
                            $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                            $stmt->bindParam(':installment_no', $installment_no, PDO::PARAM_INT);
                            $stmt->bindParam(':paid_status', $paid_status);
                            $stmt->bindParam(':tl_reward', $tl_reward);
                            $stmt->bindParam(':tl_incentive', $tl_incentive);
                            $stmt->bindParam(':unpaid_status', $rd_data['payment_status']);
                            $stmt->bindParam(':last_day_this_month', $last_day_this_month);
                            $stmt->bindParam(':incentive_status', $incentive_status);
                        }
                        else if($rd_data['usertype'] === "Head")
                        {
                            $head_reward = (($rd_data['amount'] * $rewards[$users[2]][$reward_year]) / 100);
    
                            $head_incentive = (($rd_data['amount'] * $percents[$users[2]][$installment_no_year]) / 100);
    
                            $paid_status = 'paid';
                            $incentive_status = 'incentive_unpaid';
                            $sql = "UPDATE `vn_rd_reward_incentive` SET `payment_status`= :paid_status,  `head_reward`= :head_reward,`head_incentive`= :head_incentive, `head_incentive_status` = :incentive_status WHERE `lead_id` = :lead_id AND `installment_no` = :installment_no AND `payment_status` = :unpaid_status AND date <= :last_day_this_month ";
    
                            //Prepare a Query Statement
                            $stmt = $con->prepare($sql);
    
                            $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                            $stmt->bindParam(':installment_no', $installment_no, PDO::PARAM_INT);
                            $stmt->bindParam(':paid_status', $paid_status);
                            $stmt->bindParam(':head_reward', $head_reward);
                            $stmt->bindParam(':head_incentive', $head_incentive);
                            $stmt->bindParam(':unpaid_status', $rd_data['payment_status']);
                            $stmt->bindParam(':last_day_this_month', $last_day_this_month);
                            $stmt->bindParam(':incentive_status', $incentive_status);
                        }
                        else
                        {
                            throw new PDOException('User Type not available.');
                        }

                        if($stmt->execute())
                        {
                            $count = $stmt->rowCount();
                            if($count == 1) 
                            {
                                $result['result'] = "success";                                
                            }
                            else
                            {
                                throw new PDOException("Can not update the status.");
                            }
                        }
                        else
                        {
                            throw new PDOException("Can not update the status.");
                        }      
                    }
                }
                else
                {
                    throw new PDOException('May be payment status is updated or scheme type is not RD');
                }
            } 
            else 
            {
                throw new PDOException('Can not Update the amount.');
            }

            if($result) 
            {
                $con->commit();
                return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($result));
            } 
            else 
            { 
                $con->rollBack();
                throw new PDOException('Can not insert comment.');
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