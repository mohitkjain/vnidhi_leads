<?php

class Set_Incentive_Schemes
{
    public $result;
}

$app->post('/api/admin/incentive/change_data', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $scheme_type = $parsedBody['scheme_type'];
    $user_type = $parsedBody['user_type'];
    $lead_type = $parsedBody['lead_type'];
    $duration = $parsedBody['duration'];
    $incentive = $parsedBody['incentive'];

    if(isset($user_type) && isset($scheme_type) && isset($lead_type) && isset($duration) && isset($incentive))
    {
        try
        {
            $con = connect_db();
            $sql_scheme = "";
             
            if($user_type === "Telecaller")
            {            
                $sql_scheme = "UPDATE `vn_incentive_telecaller` 
                SET `incentive`= :incentive
                WHERE `scheme_type` = :scheme_type
                AND `duration` = :duration";
            }
            else
            {
                $sql_scheme = "UPDATE `vn_incentives` 
                SET `incentive_per`= :incentive 
                WHERE `scheme_type` = :scheme_type 
                AND `user_type` = :user_type
                AND `lead_type` = :lead_type
                AND `duration` = :duration";
            }
            
            $stmt_scheme = $con->prepare($sql_scheme);
            $stmt_scheme->bindParam(':incentive', $incentive);
            $stmt_scheme->bindParam(':scheme_type', $scheme_type);
            $stmt_scheme->bindParam(':duration', $duration);

            if($user_type != "Telecaller")
            {
                $stmt_scheme->bindParam(':lead_type', $lead_type);
                $stmt_scheme->bindParam(':user_type', $user_type);
            }

            if ($stmt_scheme->execute()) 
            {
                $count = $stmt_scheme->rowCount();
                if($count > 0)
                {
                    $result = "success"; 
                }
                else
                {
                    $result = "failure";
                }       
                $obj = new Set_Incentive_Schemes();
                $obj->result = $result;
                if($obj) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($obj));
                } 
                else 
                { 
                    throw new PDOException('Can not Update Record.');
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