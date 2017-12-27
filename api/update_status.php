<?php

$app->post('/api/status/change', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $status_to = $parsedBody['status_to'];
    $lead_id = $parsedBody['lead_id'];
    $user_type = $parsedBody['user_type'];

    if(isset($status_to) && isset($lead_id) && isset($user_type))
    {
        try
        {
            $con = connect_db();
            $stmt = "";
            
            if($user_type === 'Head')
            {
                $closing_date = date('Y-m-d');
                $converted = 0;
                if($status_to === 'accepted')
                {
                    $converted = 1;
                }

                //Prepare a Query Statement
                $sql = "UPDATE `vn_leads` SET `status`= :status_to, `converted`= :converted, `closing_date`= :closing_date WHERE `lead_id` = :lead_id";
                $stmt = $con->prepare($sql);
    
                $stmt->bindParam(':status_to', $status_to, PDO::PARAM_STR);
                $stmt->bindParam(':converted', $converted, PDO::PARAM_INT);
                $stmt->bindParam(':closing_date', $closing_date, PDO::PARAM_STR);
                $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
            }
            else 
            {
                if($status_to === 'declined')
                {
                    $converted = 0;
                    $closing_date = date('Y-m-d');

                    $sql = "UPDATE `vn_leads` SET `status`= :status_to, `converted`= :converted, `closing_date`= :closing_date WHERE `lead_id` = :lead_id";
                    $stmt = $con->prepare($sql);
    
                    $stmt->bindParam(':status_to', $status_to, PDO::PARAM_STR);
                    $stmt->bindParam(':converted', $converted, PDO::PARAM_INT);
                    $stmt->bindParam(':closing_date', $closing_date, PDO::PARAM_STR);
                    $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                }
                else
                {
                     //Prepare a Query Statement
                    $sql = "UPDATE vn_leads SET `status` = :status_to WHERE `lead_id` = :lead_id";
                    $stmt = $con->prepare($sql);
        
                    $stmt->bindParam(':status_to', $status_to, PDO::PARAM_STR);
                    $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                }                
            }

            if ($stmt->execute()) 
            {
                $count = $stmt->rowCount();
                $json['result'] = "success";
               
                if($count == 1) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($json));
                } 
                else 
                { 
                    throw new PDOException('Can not update the status');
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