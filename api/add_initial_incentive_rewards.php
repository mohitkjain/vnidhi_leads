<?php

$app->post('/api/incentive-reward/initial', function ($request, $response) 
{
    require_once 'settings/config.php';
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $lead_id = $parsedBody['lead_id'];

    $users = array("Salaried", "Teamleader", "Head");
    $config = new config();

    if(isset($lead_id) )
    {
        try
        {
            $con = connect_db();

            $sql = "SELECT info.lead_type, info.scheme_id, scheme_desc.scheme_name, scheme_desc.scheme_type, duration, amount, leads.creator_id, info.user_id, assignee.usertype, assignee.tl_id as 'tl_id', head.tl_id as 'head_id' FROM `vn_lead_info` AS info INNER JOIN vn_leads leads ON info.lead_id = leads.lead_id INNER JOIN vn_users assignee ON info.user_id = assignee.user_id INNER JOIN vn_users head ON assignee.tl_id = head.user_id INNER JOIN vn_scheme_description scheme_desc ON info.scheme_id = scheme_desc.scheme_id WHERE info.lead_id = :lead_id";

            $stmt = $con->prepare($sql);    
            $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);

            if ($stmt->execute()) 
            {
                $lead_data = $stmt->fetch();
                $incentive_status = 'incentive_unpaid';
                if($lead_data['scheme_type'] === "FD")
                {
                    //We start our transaction.                    
                    $con->beginTransaction();            
                    
                    //We will need to wrap our queries inside a TRY / CATCH block.
                    //That way, we can rollback the transaction if a query fails and a PDO exception occurs.
                    try
                    {
                        $fd_target_date = date('Y-m-d');                  
                        $fd_target_month=date("m",strtotime($fd_target_date));
                        $fd_target_year=date("Y",strtotime($fd_target_date));

                        if($lead_data['usertype'] === "Teamleader" || $lead_data['usertype'] === "Salaried") 
                        {
                            $ch = curl_init();
                            $url = $config->addFD_Achieved();
                            $post_data = "user_id=".$lead_data['user_id']."&current_month=".$fd_target_month."&current_year=".$fd_target_year."&amount=".$lead_data['amount'];
                            
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
                        }

                        $row_fd_reward_count = 1;
                        $year = 1;
                        $duration_month = $lead_data['duration'];
                        if($lead_data['duration'] > 12)
                        {
                            $row_fd_reward_count = ceil($lead_data['duration'] / 12);
                        }

                        if($lead_data['lead_type'] === "company_lead")
                        {
                            $ch = curl_init();
                            $url = $config->addFD_Achieved();
                            $post_data = "user_id=".$lead_data['tl_id']."&current_month=".$fd_target_month."&current_year=".$fd_target_year."&amount=".$lead_data['amount'];
                            
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


                            $rewards = array();
                            $percents = array();

                            $user_reward; $tl_reward; $head_reward;
                            $user_incentive; $tl_incentive; $head_incentive;
                            
                            $date = date('Y-m-d');
                            $i = 1;

                            foreach($users as $user)
                            {
                                $ch = curl_init();
                                $url = $config->getrewards();
                                $post_data = "user_type=".$user."&lead_type=".$lead_data['lead_type'];
                                
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
                                    $rewards["$user"][$reward_user->year_wise] = $reward_user->reward_per;
                                }

                                $ch = curl_init();
                                $url = $config->getpercent();
                                $post_data = "scheme_type=".$lead_data['scheme_type']."&user_type=".$user."&lead_type=".$lead_data['lead_type'];
                                
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
                                    $percents["$user"][$percent_user->duration] = $percent_user->incentive_per;
                                }
                            }

                            while($row_fd_reward_count > 0)
                            {
                                if($year == 1)
                                {
                                    $user_reward = (($lead_data['amount'] * $rewards[$users[0]][1]) / 100);
                                    $tl_reward = (($lead_data['amount'] * $rewards[$users[1]][1]) / 100);
                                    $head_reward = (($lead_data['amount'] * $rewards[$users[2]][1]) / 100);

                                    $user_incentive = (($lead_data['amount'] * $percents[$users[0]][$duration_month]) / 100);
                                    $tl_incentive = (($lead_data['amount'] * $percents[$users[1]][$duration_month]) / 100);
                                    $head_incentive = (($lead_data['amount'] * $percents[$users[2]][$duration_month]) / 100);

                                    $sql = "INSERT INTO `vn_fd_reward`(`lead_id`, `date`, `reward_paid`, `user_id`, `user_reward`, `user_incentive`, `user_incentive_status`, `tl_id`, `tl_reward`, `tl_incentive`, `tl_incentive_status`, `head_id`, `head_reward`, `head_incentive`, `head_incentive_status`) VALUES (:lead_id, :date, 'paid', :user_id, :user_reward, :user_incentive, :incentive_status, :tl_id, :tl_reward, :tl_incentive, :incentive_status, :head_id,:head_reward, :head_incentive, :incentive_status)";

                                    $stmt = $con->prepare($sql);
                                    $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                    $stmt->bindParam(':date', $date);
                                    $stmt->bindParam(':user_id', $lead_data['user_id'], PDO::PARAM_INT);
                                    $stmt->bindParam(':user_reward', $user_reward, PDO::PARAM_INT);
                                    $stmt->bindParam(':user_incentive', $user_incentive);
                                    $stmt->bindParam(':tl_id', $lead_data['tl_id'], PDO::PARAM_INT);
                                    $stmt->bindParam(':tl_reward', $tl_reward, PDO::PARAM_INT);
                                    $stmt->bindParam(':tl_incentive', $tl_incentive);
                                    $stmt->bindParam(':head_id', $lead_data['head_id'], PDO::PARAM_INT);
                                    $stmt->bindParam(':head_reward', $head_reward, PDO::PARAM_INT);
                                    $stmt->bindParam(':head_incentive', $head_incentive);
                                    $stmt->bindParam(':incentive_status', $incentive_status);
                                }
                                else
                                {
                                   /* $user_reward = (($lead_data['amount'] * $rewards[$users[0]][2]) / 100);
                                    $tl_reward = (($lead_data['amount'] * $rewards[$users[1]][2]) / 100);
                                    $head_reward = (($lead_data['amount'] * $rewards[$users[2]][2]) / 100);
                                    */
                                    $sql = "INSERT INTO `vn_fd_reward`(`lead_id`, `date`, `reward_paid`, `user_id`, `tl_id`, `head_id`) VALUES (:lead_id, :date, 'unpaid', :user_id, :tl_id, :head_id)";

                                    $stmt = $con->prepare($sql);
                                    $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                    $stmt->bindParam(':date', $date);
                                    $stmt->bindParam(':user_id', $lead_data['user_id'], PDO::PARAM_INT);         
                                    $stmt->bindParam(':tl_id', $lead_data['tl_id'], PDO::PARAM_INT);
                                    $stmt->bindParam(':head_id', $lead_data['head_id'], PDO::PARAM_INT);       
                                }                                
                                
                                if($stmt->execute())
                                {
                                    $row_fd_reward_count--;
                                    $date = date('Y-m-d', strtotime('+'.$i.' years'));
                                    $year = 2;
                                    $i++;
                                } 
                                else
                                {
                                    throw new PDOException("Insertion Error");
                                }                                    
                            }

                             // Add Telecaller Incentive
                             $current_date = date('Y-m-d');
                             $ch = curl_init();
                             $url = $config->add_telecaller_incentive();
                             $post_data = "lead_id=".$lead_id."&creator_id=".$lead_data['creator_id']."&duration=".$lead_data['duration']."&amount=".$lead_data['amount']."&date=".$current_date."&scheme_type=".$lead_data['scheme_type']."&lead_type=".$lead_data['lead_type'];
                             
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
                        }

                        else if($lead_data['lead_type'] === "direct_lead")
                        {
                            $tl_reward; $head_reward;
                            $date = date('Y-m-d');
                            $i = 1;

                            $ch = curl_init();
                            $url = $config->getrewards();
                            $usertype = $lead_data['usertype'];
                            $post_data = "user_type=".$usertype."&lead_type=".$lead_data['lead_type'];
                            
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

                            $rewards["$usertype"] = json_decode($output_data);
                            foreach($rewards["$usertype"] as $reward_user)
                            {
                                $rewards["$usertype"][$reward_user->year_wise] = $reward_user->reward_per;
                            }

                            $ch = curl_init();
                            $url = $config->getpercent();
                            $post_data = "scheme_type=".$lead_data['scheme_type']."&user_type=".$usertype."&lead_type=".$lead_data['lead_type'];
                            
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

                            $percents["$usertype"] = json_decode($output_data);

                            foreach($percents["$usertype"] as $percent_user)
                            {
                                $percents["$usertype"][$percent_user->duration] = $percent_user->incentive_per;
                            }

                            if($lead_data['usertype'] === "Teamleader")
                            {
                                while($row_fd_reward_count > 0)
                                {
                                    if($year == 1)
                                    {
                                        $tl_reward = (($lead_data['amount'] * $rewards[$users[1]][1]) / 100);

                                        $tl_incentive = (($lead_data['amount'] * $percents[$users[1]][$duration_month]) / 100);
    
                                        $sql = "INSERT INTO `vn_fd_reward`(`lead_id`, `date`, `reward_paid`, `tl_id`, `tl_reward`, `tl_incentive`, `tl_incentive_status`) VALUES (:lead_id, :date, 'paid', :tl_id, :tl_reward, :tl_incentive, :incentive_status)";
    
                                        $stmt = $con->prepare($sql);
                                        $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                        $stmt->bindParam(':date', $date);
                                        $stmt->bindParam(':tl_id', $lead_data['user_id'], PDO::PARAM_INT);
                                        $stmt->bindParam(':tl_reward', $tl_reward, PDO::PARAM_INT);
                                        $stmt->bindParam(':tl_incentive', $tl_incentive);
                                        $stmt->bindParam(':incentive_status', $incentive_status);
                                    }
                                    else
                                    {
                                       /* 
                                        $tl_reward = (($lead_data['amount'] * $rewards[$users[1]][2]) / 100);
                                        */
                                        $sql = "INSERT INTO `vn_fd_reward`(`lead_id`, `date`, `reward_paid`, `tl_id`) VALUES (:lead_id, :date, 'unpaid', :tl_id)";
    
                                        $stmt = $con->prepare($sql);
                                        $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                        $stmt->bindParam(':date', $date);
                                        $stmt->bindParam(':tl_id', $lead_data['user_id'], PDO::PARAM_INT);   
                                    }                    
                                    if($stmt->execute())
                                    {
                                        $row_fd_reward_count--;
                                        $date = date('Y-m-d', strtotime('+'.$i.' years'));
                                        $year = 2;
                                        $i++;
                                    }   
                                    else
                                    {
                                        throw new PDOException("Insertion Error");
                                    }                                  
                                }
                            }
                            else if($lead_data['usertype'] === "Head")
                            {
                                while($row_fd_reward_count > 0)
                                {
                                    if($year == 1)
                                    {
                                        $head_reward = (($lead_data['amount'] * $rewards[$users[2]][1]) / 100);
                                        $head_incentive = (($lead_data['amount'] * $percents[$users[2]][$duration_month]) / 100);
    
                                        $sql = "INSERT INTO `vn_fd_reward`(`lead_id`, `date`, `reward_paid`, `head_id`, `head_reward`, `head_incentive`, `head_incentive_status`) VALUES (:lead_id, :date, 'paid', :head_id, :head_reward, :head_incentive, :incentive_status)";
    
                                        $stmt = $con->prepare($sql);
                                        $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                        $stmt->bindParam(':date', $date);
                                        $stmt->bindParam(':head_id', $lead_data['user_id'], PDO::PARAM_INT);
                                        $stmt->bindParam(':head_reward', $head_reward, PDO::PARAM_INT);
                                        $stmt->bindParam(':head_incentive', $head_incentive);
                                        $stmt->bindParam(':incentive_status', $incentive_status);
                                    }
                                    else
                                    {
                                       /* 
                                        $tl_reward = (($lead_data['amount'] * $rewards[$users[1]][2]) / 100);
                                        */
                                        $sql = "INSERT INTO `vn_fd_reward`(`lead_id`, `date`, `reward_paid`, `head_id`) VALUES (:lead_id, :date, 'unpaid', :head_id)";
    
                                        $stmt = $con->prepare($sql);
                                        $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                        $stmt->bindParam(':date', $date);
                                        $stmt->bindParam(':head_id', $lead_data['user_id'], PDO::PARAM_INT);   
                                    }                    
                                    if($stmt->execute())
                                    {
                                        $row_fd_reward_count--;
                                        $date = date('Y-m-d', strtotime('+'.$i.' years'));
                                        $year = 2;
                                        $i++;
                                    } 
                                    else
                                    {
                                        throw new PDOException("Insertion Error");
                                    }                                    
                                }
                            }   
                        }

                        $con->commit();
                        $result['result'] = "success";
                        return $response->withStatus(200)
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode($result));
                    }
                    catch(Exception $ex)
                    {
                        //An exception has occured, which means that one of our database queries failed.
                        $errors = array();
                        $errors[0]['result'] = "failure";
                        $errors[0]['error_msg'] = $ex->getMessage();
                        //Rollback the transaction.
                        $con->rollBack();
                        
                        return $response->withStatus(404)
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode($errors));
                    }
                }
                else if($lead_data['scheme_type'] === "RD")
                {
                    //We start our transaction.                    
                    $con->beginTransaction();            
                    
                    //We will need to wrap our queries inside a TRY / CATCH block.
                    //That way, we can rollback the transaction if a query fails and a PDO exception occurs.
                    try
                    {
                        $months = $lead_data['duration'];
                        $initial_month = 1;
                        if($lead_data['lead_type'] === "company_lead")
                        {
                            $rewards = array();
                            $percents = array();

                            $user_reward; $tl_reward; $head_reward;
                            $user_incentive; $tl_incentive; $head_incentive;

                            $date = date('Y-m-d');
                            $installment_no = 1;

                            foreach($users as $user)
                            {
                                $ch = curl_init();
                                $url = $config->getrewards();
                                $post_data = "user_type=".$user."&lead_type=".$lead_data['lead_type'];
                                
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
                                    $rewards["$user"][$reward_user->year_wise] = $reward_user->reward_per;
                                }

                                $ch = curl_init();
                                $url = $config->getpercent();
                                $post_data = "scheme_type=".$lead_data['scheme_type']."&user_type=".$user."&lead_type=".$lead_data['lead_type'];
                                
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
                                    $percents["$user"][$percent_user->duration] = $percent_user->incentive_per;
                                }
                            }

                            while($months > 0)
                            {
                                if($initial_month == 1)
                                {
                                    $payment_status = "paid";
                                    $user_reward = (($lead_data['amount'] * $rewards[$users[0]][1]) / 100);
                                    $tl_reward = (($lead_data['amount'] * $rewards[$users[1]][1]) / 100);
                                    $head_reward = (($lead_data['amount'] * $rewards[$users[2]][1]) / 100);

                                    $user_incentive = (($lead_data['amount'] * $percents[$users[0]][1]) / 100);
                                    $tl_incentive = (($lead_data['amount'] * $percents[$users[1]][1]) / 100);
                                    $head_incentive = (($lead_data['amount'] * $percents[$users[2]][1]) / 100);

                                    $sql = "INSERT INTO `vn_rd_reward_incentive`(`lead_id`, `installment_no`, `date`, `payment_status`, `user_id`, `user_reward`, `user_incentive`, `user_incentive_status`, `tl_id`, `tl_reward`, `tl_incentive`, `tl_incentive_status`, `head_id`, `head_reward`, `head_incentive`, `head_incentive_status`) VALUES(:lead_id, :installment_no, :date, :payment_status, :user_id, :user_reward, :user_incentive, :incentive_status, :tl_id, :tl_reward, :tl_incentive, :incentive_status, :head_id, :head_reward, :head_incentive, :incentive_status)";

                                    $stmt = $con->prepare($sql);
                                    $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                    $stmt->bindParam(':installment_no', $installment_no, PDO::PARAM_INT);
                                    $stmt->bindParam(':date', $date);
                                    $stmt->bindParam(':payment_status', $payment_status);
                                    $stmt->bindParam(':user_id', $lead_data['user_id'], PDO::PARAM_INT);
                                    $stmt->bindParam(':user_reward', $user_reward);
                                    $stmt->bindParam(':user_incentive', $user_incentive);
                                    $stmt->bindParam(':tl_id', $lead_data['tl_id'], PDO::PARAM_INT);
                                    $stmt->bindParam(':tl_reward', $tl_reward);
                                    $stmt->bindParam(':tl_incentive', $tl_incentive);
                                    $stmt->bindParam(':head_id', $lead_data['head_id'], PDO::PARAM_INT);
                                    $stmt->bindParam(':head_reward', $head_reward);
                                    $stmt->bindParam(':head_incentive', $head_incentive);
                                    $stmt->bindParam(':incentive_status', $incentive_status);
                                }
                                else
                                {
                                    $payment_status = "unpaid";
                                   /* $user_reward = (($lead_data['amount'] * $rewards[$users[0]][1]) / 100);
                                    $tl_reward = (($lead_data['amount'] * $rewards[$users[1]][1]) / 100);
                                    $head_reward = (($lead_data['amount'] * $rewards[$users[2]][1]) / 100);

                                    $user_incentive = (($lead_data['amount'] * $percents[$users[0]][1]) / 100);
                                    $tl_incentive = (($lead_data['amount'] * $percents[$users[1]][1]) / 100);
                                    $head_incentive = (($lead_data['amount'] * $percents[$users[2]][1]) / 100);
                                    */
                                    $sql = "INSERT INTO `vn_rd_reward_incentive`(`lead_id`, `installment_no`, `date`, `payment_status`, `user_id`, `tl_id`, `head_id`) VALUES (:lead_id, :installment_no, :date, :payment_status, :user_id, :tl_id, :head_id)";

                                    $stmt = $con->prepare($sql);
                                    $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                    $stmt->bindParam(':installment_no', $installment_no, PDO::PARAM_INT);
                                    $stmt->bindParam(':date', $date);
                                    $stmt->bindParam(':payment_status', $payment_status);
                                    $stmt->bindParam(':user_id', $lead_data['user_id'], PDO::PARAM_INT);        
                                    $stmt->bindParam(':tl_id', $lead_data['tl_id'], PDO::PARAM_INT);
                                    $stmt->bindParam(':head_id', $lead_data['head_id'], PDO::PARAM_INT);       
                                }                                
                                
                                if($stmt->execute())
                                {
                                    $months--;
                                    $date = date('Y-m-d', strtotime('+'.$installment_no.' months'));
                                    $initial_month = 2;
                                    $installment_no++;
                                }
                                else
                                {
                                    throw new PDOException("Insertion Error");
                                }                                  
                            }

                             // Add Telecaller Incentive
                             $current_date = date('Y-m-d');
                             $ch = curl_init();
                             $url = $config->add_telecaller_incentive();
                             $post_data = "lead_id=".$lead_id."&creator_id=".$lead_data['creator_id']."&duration=".$lead_data['duration']."&amount=".$lead_data['amount']."&date=".$current_date."&scheme_type=".$lead_data['scheme_type']."&lead_type=".$lead_data['lead_type'];
                             
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

                        }
                        else if($lead_data['lead_type'] === "direct_lead")
                        {
                            $rewards = array();
                            $percents = array();

                            $tl_reward; $head_reward;
                            $tl_incentive; $head_incentive;

                            $date = date('Y-m-d');
                            $installment_no = 1;

                            $ch = curl_init();
                            $url = $config->getrewards();
                            $usertype = $lead_data['usertype'];
                            $post_data = "user_type=".$usertype."&lead_type=".$lead_data['lead_type'];
                            
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

                            $rewards["$usertype"] = json_decode($output_data);
                            foreach($rewards["$usertype"] as $reward_user)
                            {
                                $rewards["$usertype"][$reward_user->year_wise] = $reward_user->reward_per;
                            }

                            $ch = curl_init();
                            $url = $config->getpercent();
                            $post_data = "scheme_type=".$lead_data['scheme_type']."&user_type=".$usertype."&lead_type=".$lead_data['lead_type'];
                            
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

                            $percents["$usertype"] = json_decode($output_data);

                            foreach($percents["$usertype"] as $percent_user)
                            {
                                $percents["$usertype"][$percent_user->duration] = $percent_user->incentive_per;
                            }

                            if($lead_data['usertype'] === "Teamleader")
                            {
                                while($months > 0)
                                {
                                    if($initial_month == 1)
                                    {
                                        $payment_status = "paid";
                                        $tl_reward = (($lead_data['amount'] * $rewards[$users[1]][1]) / 100);
                                        $tl_incentive = (($lead_data['amount'] * $percents[$users[1]][1]) / 100);
    
                                        $sql = "INSERT INTO `vn_rd_reward_incentive`(`lead_id`, `installment_no`, `date`, `payment_status`, `tl_id`, `tl_reward`, `tl_incentive`, `tl_incentive_status`) VALUES(:lead_id, :installment_no, :date, :payment_status, :tl_id, :tl_reward, :tl_incentive, :incentive_status)";

                                        $stmt = $con->prepare($sql);
                                        $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                        $stmt->bindParam(':installment_no', $installment_no, PDO::PARAM_INT);
                                        $stmt->bindParam(':date', $date);
                                        $stmt->bindParam(':payment_status', $payment_status);
                                        $stmt->bindParam(':tl_id', $lead_data['user_id'], PDO::PARAM_INT);
                                        $stmt->bindParam(':tl_reward', $tl_reward);
                                        $stmt->bindParam(':tl_incentive', $tl_incentive);
                                        $stmt->bindParam(':incentive_status', $incentive_status);
                                    }
                                    else
                                    {
                                        $payment_status = "unpaid";
                                        /* 
                                         $tl_reward = (($lead_data['amount'] * $rewards[$users[1]][2]) / 100); 
                                         $tl_incentive = (($lead_data['amount'] * $percents[$users[1]][2]) / 100);  */
                                        $sql = "INSERT INTO `vn_rd_reward_incentive`(`lead_id`, `installment_no`, `date`, `payment_status`, `tl_id`) VALUES (:lead_id, :installment_no, :date, :payment_status,  :tl_id)";
     
                                        $stmt = $con->prepare($sql);
                                        $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                        $stmt->bindParam(':installment_no', $installment_no, PDO::PARAM_INT);
                                        $stmt->bindParam(':date', $date);
                                        $stmt->bindParam(':payment_status', $payment_status);
                                        $stmt->bindParam(':tl_id', $lead_data['user_id'], PDO::PARAM_INT);    
                                    }                    
                                    if($stmt->execute())
                                    {
                                        $months--;
                                        $date = date('Y-m-d', strtotime('+'.$installment_no.' months'));
                                        $initial_month = 2;
                                        $installment_no++;
                                    }   
                                    else
                                    {
                                        throw new PDOException("Insertion Error");
                                    }                                  
                                }
                            }

                            if($lead_data['usertype'] === "Head")
                            {
                                while($months > 0)
                                {
                                    if($initial_month == 1)
                                    {
                                        $payment_status = "paid";
                                        $head_reward = (($lead_data['amount'] * $rewards[$users[2]][1]) / 100);
                                        $head_incentive = (($lead_data['amount'] * $percents[$users[2]][1]) / 100);
    
                                        $sql = "INSERT INTO `vn_rd_reward_incentive`(`lead_id`, `installment_no`, `date`, `payment_status`, `head_id`, `head_reward`, `head_incentive`, `head_incentive_status`) VALUES(:lead_id, :installment_no, :date, :payment_status, :head_id, :head_reward, :head_incentive, :incentive_status)";

                                        $stmt = $con->prepare($sql);
                                        $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                        $stmt->bindParam(':installment_no', $installment_no, PDO::PARAM_INT);
                                        $stmt->bindParam(':date', $date);
                                        $stmt->bindParam(':payment_status', $payment_status);
                                        $stmt->bindParam(':head_id', $lead_data['user_id'], PDO::PARAM_INT);
                                        $stmt->bindParam(':head_reward', $head_reward);
                                        $stmt->bindParam(':head_incentive', $head_incentive);
                                        $stmt->bindParam(':incentive_status', $incentive_status);
                                    }
                                    else
                                    {
                                        $payment_status = "unpaid";
                                        /* 
                                         $tl_reward = (($lead_data['amount'] * $rewards[$users[1]][2]) / 100); 
                                         $tl_incentive = (($lead_data['amount'] * $percents[$users[1]][2]) / 100);  */
                                        $sql = "INSERT INTO `vn_rd_reward_incentive`(`lead_id`, `installment_no`, `date`, `payment_status`, `head_id`) VALUES (:lead_id, :installment_no, :date, :payment_status,  :head_id)";
     
                                        $stmt = $con->prepare($sql);
                                        $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                                        $stmt->bindParam(':installment_no', $installment_no, PDO::PARAM_INT);
                                        $stmt->bindParam(':date', $date);
                                        $stmt->bindParam(':payment_status', $payment_status);
                                        $stmt->bindParam(':head_id', $lead_data['user_id'], PDO::PARAM_INT);    
                                    }                    
                                    if($stmt->execute())
                                    {
                                        $months--;
                                        $date = date('Y-m-d', strtotime('+'.$installment_no.' months'));
                                        $initial_month = 2;
                                        $installment_no++;
                                    }   
                                    else
                                    {
                                        throw new PDOException("Insertion Error");
                                    }                                  
                                }
                            }
                        }

                        $con->commit();
                        $result['result'] = "success";
                        return $response->withStatus(200)
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode($result));
                    }
                    catch(Exception $ex)
                    {
                        //An exception has occured, which means that one of our database queries failed.
                        $errors = array();
                        $errors[0]['result'] = "failure";
                        $errors[0]['error_msg'] = $ex->getMessage();
                        //Rollback the transaction.
                        $con->rollBack();
                        
                        return $response->withStatus(404)
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode($errors));
                    }
                }
            }
            else
            {
                throw new PDOException("No Records Found");
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