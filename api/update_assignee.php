<?php

$app->post('/api/users/update/assignee', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $assignee_id = $parsedBody['assignee_id'];
    $lead_id = $parsedBody['lead_id'];

    if(isset($assignee_id) && isset($lead_id))
    {
        try
        {
            $con = connect_db();

            //Prepare a Query Statement
            $sql = "UPDATE `vn_leads` SET `assignee_id` = :assignee_id WHERE lead_id = :lead_id";
            $stmt = $con->prepare($sql);

            $stmt->bindParam(':assignee_id', $assignee_id, PDO::PARAM_INT);
            $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);

            if ($stmt->execute()) 
            {
                $result['result'] = "success";
                $count = $stmt->rowCount();
                if($count == 1) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($result));
                } 
                else 
                { 
                    throw new PDOException('Can not update the assignee');
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