<?php

$app->post('/api/redeem/data', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $rewards_points = $parsedBody['rewards_points'];

    if(isset($rewards_points))
    {
        try
        {
             $con = connect_db();
             
            //Prepare a Query Statement
            $sql = "SELECT `rewards_points`, `reward` FROM `vn_reedemption_data` WHERE `rewards_points` <= :rewards_points";
            $stmt = $con->prepare($sql);
            
            $stmt->bindParam(':rewards_points', $rewards_points);

            if ($stmt->execute()) 
            {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if($data) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($data));
                } 
                else 
                { 
                    throw new PDOException('No rewards available for this user');
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