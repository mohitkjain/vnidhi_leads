<?php

$app->post('/api/users/salaried', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $userid = $parsedBody['userid'];

    if(isset($userid))
    {
        try
        {
            $con = connect_db();

            //Prepare a Query Statement
            $stmt = $con->prepare("SELECT user_id, fname, lname FROM vn_users WHERE tl_id = (SELECT tl_id FROM vn_users WHERE user_id = :userid) AND usertype = :usertype AND active = 1 ");
            $usertype = "Salaried";

            $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
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
                    throw new PDOException('No records found');
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