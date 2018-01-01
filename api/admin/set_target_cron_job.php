<?php

$app->get('/api/cron/set_target/current_month', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    try
    {
        $con = connect_db();

        $current_date = date('Y-m-d');
        $pre_month = date("m", strtotime($current_date . " last month"));
        $pre_year = date("Y", strtotime($current_date . " last month"));
        $current_month = date('m');
        $current_year = date('Y');
        $result = array();
    
        $sql = "SELECT `user_id`, `fname`, `lname`, `usertype` FROM `vn_users` WHERE (`usertype` = 'Salaried' OR `usertype` = 'Teamleader') AND `active` = 1";
        $stmt = $con->prepare($sql);
    
        if($stmt->execute())
        {
            $user_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            foreach($user_data as $user)
            {
                $user_id = $user['user_id'];
                $fname = $user['fname'];
                $lname = $user['lname'];
                $usertype = $user['usertype'];
                //Check is there current month target exist or not
                $cur_sql = "SELECT `target_amount` FROM `vn_target_fd` WHERE `user_id` = :user_id AND `target_year` = :current_year AND `target_month` = :current_month";
    
                $stmt_cur = $con->prepare($cur_sql);
                $stmt_cur->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_cur->bindParam(':current_month', $current_month, PDO::PARAM_INT);
                $stmt_cur->bindParam(':current_year', $current_year, PDO::PARAM_INT);
    
                if($stmt_cur->execute())
                {
                    $cur_target = $stmt_cur->fetch();
                    if(!isset($cur_target['target_amount']))
                    {
                       $pre_sql = "SELECT `target_amount` FROM `vn_target_fd` WHERE `user_id` = :user_id AND `target_year` = :pre_year AND `target_month` = :pre_month";
    
                        $stmt_pre = $con->prepare($pre_sql);
                        $stmt_pre->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        $stmt_pre->bindParam(':pre_month', $pre_month, PDO::PARAM_INT);
                        $stmt_pre->bindParam(':pre_year', $pre_year, PDO::PARAM_INT);
    
                        if($stmt_pre->execute())
                        {
                            $pre_target = $stmt_pre->fetch();
                            $target_amount = $pre_target['target_amount'];
    
                            if(isset($pre_target['target_amount']))
                            {
                               $target_amount = $pre_target['target_amount'];
                            }
                            else
                            {
                               $target_amount = 0;
                            }
    
                            $sql_insert = "INSERT INTO `vn_target_fd`(`user_id`, `target_amount`, `target_year`, `target_month`) VALUES (:user_id, :target_amount, :current_year, :current_month)";
    
                            $stmt_insert = $con->prepare($sql_insert);
    
                            $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                            $stmt_insert->bindParam(':target_amount', $target_amount);
                            $stmt_insert->bindParam(':current_month', $current_month, PDO::PARAM_INT);
                            $stmt_insert->bindParam(':current_year', $current_year, PDO::PARAM_INT);
    
                            if($stmt_insert->execute())
                            {
                                $id = $con->lastInsertId();
                                if($id >= 1)
                                {
                                    $result[$user_id]['result'] = "success";
                                    $result[$user_id]['name'] = $fname. " " . $lname;
                                    $result[$user_id]['usertype'] = $usertype;
                                    $result[$user_id]['target'] = $target_amount;
                                    $result[$user_id]['month_year'] = date('F'). ", ".$current_year ;
                                }
                                else
                                {
                                    $result[$user_id]['result'] = "failure";
                                    $result[$user_id]['name'] = $fname. " " . $lname;
                                    $result[$user_id]['usertype'] = $usertype;
                                    $result[$user_id]['target'] = $target_amount;
                                    $result[$user_id]['month_year'] = date('F'). ", ".$current_year ;
                                }
                            }
                            else
                            {
                                $result[$user_id]['result'] = "failure";
                            }
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