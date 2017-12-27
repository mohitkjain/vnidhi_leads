<?php

$app->post('/api/status/available', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $status_from = $parsedBody['status_from'];
    $usertype = $parsedBody['usertype'];

    if(isset($status_from) && isset($usertype))
    {
        try
        {
             $con = connect_db();
             
            //Prepare a Query Statement
            $sql = "SELECT `status_to` FROM vn_status_flow WHERE `status_from` = :status_from AND permission_user = :usertype";
            $stmt = $con->prepare($sql);
            
            $stmt->bindParam(':status_from', $status_from, PDO::PARAM_STR);
            $stmt->bindParam(':usertype', $usertype, PDO::PARAM_STR);

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
                    throw new PDOException('No status available for this user');
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