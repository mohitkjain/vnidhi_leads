<?php

$app->get('/api/cron/fd_reward/add', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';

    try
    {
        $con = connect_db();

        $current_date = date('Y-m-d');
        $company_lead_users = array("Salaried", "Teamleader", "Head");
        $company_lead_rewards = array();
        
        $direct_lead_users = array("Teamleader", "Head");
        $direct_lead_rewards = array();
        $result = array();
        $config = new config();

        foreach($company_lead_users as $company_lead_user)
        {
            $ch = curl_init();
            $url = $config->getrewards();
            $post_data = "user_type=".$company_lead_user."&lead_type=company_lead";
            
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

            $company_lead_rewards["$company_lead_user"] = json_decode($output_data);
            foreach($company_lead_rewards["$company_lead_user"] as $reward_user)
            {
                $company_lead_rewards["$company_lead_user"][$reward_user->year_wise] = $reward_user->reward_per;
            }
        }

        foreach($direct_lead_users as $direct_lead_user)
        {
            $ch = curl_init();
            $url = $config->getrewards();
            $post_data = "user_type=".$direct_lead_user."&lead_type=direct_lead";
            
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

            $direct_lead_rewards["$direct_lead_user"] = json_decode($output_data);
            foreach($direct_lead_rewards["$direct_lead_user"] as $reward_user)
            {
                $direct_lead_rewards["$direct_lead_user"][$reward_user->year_wise] = $reward_user->reward_per;
            }
        }
    
        $sql = "SELECT `id`, `lead_id`, `date`, `reward_paid` FROM `vn_fd_reward` WHERE `date` = :current_date AND `reward_paid` = 'unpaid'";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':current_date', $current_date);
    
        if($stmt->execute())
        {
            $reward_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if(count($reward_data) > 0)
            {
                foreach($reward_data as $reward)
                {
                    $id = $reward['id'];
                    $lead_id = $reward['lead_id'];
        
                    //Check is there current month target exist or not
                    $cur_sql = "SELECT info.lead_id, info.lead_type, scheme_desc.scheme_type, info.amount, assignee.usertype FROM vn_lead_info info INNER JOIN vn_users assignee ON info.user_id = assignee.user_id INNER JOIN vn_scheme_description scheme_desc ON info.scheme_id = scheme_desc.scheme_id WHERE info.lead_id = :lead_id";                
        
                    $stmt_cur = $con->prepare($cur_sql);
                    $stmt_cur->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
        
                    if($stmt_cur->execute())
                    {
                        $lead_data = $stmt_cur->fetch();
    
                        $lead_type = $lead_data['lead_type'];
                        $scheme_type = $lead_data['scheme_type'];
                        $duration = $lead_data['duration'];
                        $amount = $lead_data['amount'];
                        $usertype = $lead_data['usertype'];
    
                        if($scheme_type === "FD")
                        {
                            $stmt_update;
                            $user_reward; $tl_reward; $head_reward;
                            if($lead_type === "company_lead")
                            {
                                $user_reward = (($amount * $company_lead_rewards[$company_lead_users[0]][2]) / 100);
                                $tl_reward = (($amount * $company_lead_rewards[$company_lead_users[1]][2]) / 100);
                                $head_reward = (($amount * $company_lead_rewards[$company_lead_users[2]][2]) / 100);
    
                                $sql_update = "UPDATE `vn_fd_reward` SET `reward_paid` = 'paid', `user_reward`= :user_reward, `tl_reward`= :tl_reward, `head_reward`= :head_reward WHERE `id`= :id AND `lead_id`= :lead_id AND`date`= :current_date";
    
                                //Prepare a Query Statement
                                $stmt_update = $con->prepare($sql_update);
    
                                $stmt_update->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                $stmt_update->bindParam(':id', $id, PDO::PARAM_INT);
                                $stmt_update->bindParam(':current_date', $current_date);
                                $stmt_update->bindParam(':user_reward', $user_reward);
                                $stmt_update->bindParam(':tl_reward', $tl_reward);
                                $stmt_update->bindParam(':head_reward', $head_reward);       
                            }
                            else if($lead_type === "direct_lead")
                            {
                                if($usertype === "Teamleader")
                                {
                                    $tl_reward = (($amount * $direct_lead_rewards[$direct_lead_users[0]][2]) / 100);
        
                                    $sql_update = "UPDATE `vn_fd_reward` SET `reward_paid` = 'paid', `tl_reward`= :tl_reward WHERE `id`= :id AND `lead_id`= :lead_id AND`date`= :current_date";
        
                                    //Prepare a Query Statement
                                    $stmt_update = $con->prepare($sql_update);
        
                                    $stmt_update->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                    $stmt_update->bindParam(':id', $id, PDO::PARAM_INT);
                                    $stmt_update->bindParam(':current_date', $current_date);
                                    $stmt_update->bindParam(':tl_reward', $tl_reward);
                                }
                                else if($usertype === "Head")
                                {
                                    $head_reward = (($amount * $direct_lead_rewards[$direct_lead_users[1]][2]) / 100);
        
                                    $sql_update = "UPDATE `vn_fd_reward` SET `reward_paid` = 'paid', `head_reward`= :head_reward WHERE `id`= :id AND `lead_id`= :lead_id AND`date`= :current_date";
        
                                    //Prepare a Query Statement
                                    $stmt_update = $con->prepare($sql_update);
        
                                    $stmt_update->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                    $stmt_update->bindParam(':id', $id, PDO::PARAM_INT);
                                    $stmt_update->bindParam(':current_date', $current_date);
                                    $stmt_update->bindParam(':head_reward', $head_reward);
                                }
                            }
    
                            if($stmt_update->execute())
                            {
                                $count = $stmt_update->rowCount();
                                if($count == 1) 
                                {
                                    $result[$id]['result'] = "success";                                
                                }
                                else
                                {
                                    $result[$id]['result'] = "failure";
                                }
                            }
                            else
                            {
                                $result[$id]['result'] = "failure";
                            } 
                        }
                    }
                }
                if(count($result) > 0)
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($result));
                }
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