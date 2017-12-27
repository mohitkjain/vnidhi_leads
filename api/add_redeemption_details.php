<?php

$app->post('/api/redeem/insert', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $reedem_points = $parsedBody['rewards_points'];
    $award = $parsedBody['reward'];
    $user_id = $parsedBody['user_id'];

    $result = array();
    
    if(isset($reedem_points) && isset($award) && isset($user_id))
    {
        try
        {
            $con = connect_db();
            
            $current_date = date('Y-m-d');
            //Prepare a Query Statement
            $sql = "INSERT INTO `vn_reedemption_history`(`user_id`, `date`, `reedem_points`, `award`) VALUES (:user_id, :date, :reedem_points, :award)";
            $stmt = $con->prepare($sql);
            
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':date', $current_date);
            $stmt->bindParam(':reedem_points', $reedem_points);
            $stmt->bindParam(':award', $award);

            if ($stmt->execute()) 
            {
                $id = $con->lastInsertId();
                if($id >= 1)
                {
                    $result['result'] = "success";
                }
                else
                {
                    $result['result'] = "failure";
                }
            } 
            if($result) 
            {
                return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($result));
            } 
            else 
            { 
                throw new PDOException('No rewards available for this user');
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