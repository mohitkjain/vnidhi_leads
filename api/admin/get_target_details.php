<?php

$app->get('/api/admin/get_targets', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    require_once '../api/settings/config.php';
    setlocale(LC_MONETARY, 'en_IN');
    try
    {
        $config = new config();
        $result = array();
        $con = connect_db();

        $sql = "SELECT `user_id`, CONCAT(`fname`, ' ', `lname`) AS 'user_name', `usertype`, `position` FROM `vn_users` WHERE (`usertype` = 'Salaried' OR `usertype` = 'Teamleader') AND `active` = 1";

        $stmt = $con->prepare($sql);

        if($stmt->execute())
        {
            $user_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            foreach($user_data as $user)
            {
                $user_id = $user['user_id'];
                $ch = curl_init();
                
                $url = $config->get_current_month_target_details() ."". $user_id;

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, 0);// No header in the result 
                
                // Fetch and return content, save it.
                $current_data = curl_exec($ch);
                curl_close($ch);

                if(isset($current_data))
                {
                    $current_data = json_decode($current_data);

                    $ch = curl_init();
                    
                    $url = $config->get_pre_month_target_details() ."". $user_id;
    
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_VERBOSE, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HEADER, 0);// No header in the result 
                    
                    // Fetch and return content, save it.
                    $pre_data = curl_exec($ch);
                    curl_close($ch);

                    if(isset($pre_data))
                    {
                        $pre_data = json_decode($pre_data);

                        $result["$user_id"]['user_name'] = $user['user_name'];
                        $result["$user_id"]['usertype'] = $user['usertype'];
                        $result["$user_id"]['position'] = $user['position'];
                        $result["$user_id"]['current_month'] = $current_data->current_month;
                        $result["$user_id"]['current_year'] = $current_data->current_year;
                        $result["$user_id"]['current_month_target'] = money_format('%!i', $current_data->current_month_target);
                        $result["$user_id"]['current_month_achieved'] = money_format('%!i',$current_data->current_month_achieved);
                        $result["$user_id"]['pre_month'] = $pre_data->pre_month;
                        $result["$user_id"]['pre_year'] = $pre_data->pre_year;
                        $result["$user_id"]['pre_month_target'] = money_format('%!i',$pre_data->pre_month_target);
                        $result["$user_id"]['pre_month_achieved'] = money_format('%!i',$pre_data->pre_month_achieved);
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
        else 
        { 
            throw new PDOException('No records found');
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