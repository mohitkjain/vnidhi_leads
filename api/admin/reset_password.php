<?php

$app->post('/api/admin/users/update/password', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $user_id = $parsedBody['user_id'];
    $password = $parsedBody['password'];

    if(!empty($user_id) && !empty($password))
    {
        try
        {
            $con = connect_db();

            $password = base64_encode($password);

            //Prepare a Query Statement
            $sql = "UPDATE `vn_users` SET `password` = :password WHERE `user_id` = :user_id";
            $stmt = $con->prepare($sql);

            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

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
                    throw new PDOException('Can not update the password');
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