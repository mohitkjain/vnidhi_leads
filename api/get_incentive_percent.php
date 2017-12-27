<?php

$app->post('/api/incentive-percent', function ($request, $response)
{
    require_once 'settings/dbconnect.php';
    
    $parsedBody = $request->getParsedBody();
    $scheme_type = $parsedBody['scheme_type'];
    $user_type = $parsedBody['user_type'];
    $lead_type = $parsedBody['lead_type'];

    try
    {
        if(isset($user_type) && isset($lead_type) && isset($scheme_type))
        {
             $con = connect_db();

            //Prepare a Query Statement
            $sql = "SELECT `duration`, `incentive_per` FROM `vn_incentives` WHERE `scheme_type` = :scheme_type AND `user_type` = :user_type AND `lead_type` = :lead_type";
            
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':scheme_type', $scheme_type, PDO::PARAM_STR);
            $stmt->bindParam(':user_type', $user_type, PDO::PARAM_STR);
            $stmt->bindParam(':lead_type', $lead_type, PDO::PARAM_STR);

            if ($stmt->execute()) 
            {
                $incentive_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if($incentive_data) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($incentive_data));
                } 
                else 
                { 
                    throw new PDOException('No records found');
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
