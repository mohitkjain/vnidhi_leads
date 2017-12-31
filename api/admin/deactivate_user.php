<?php

$app->post('/api/admin/deactivate_user', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $user_id = $parsedBody['user_id'];
    //$active = $parsedBody['active'];
    $usertype = $parsedBody['usertype'];
    $tl_id = $parsedBody['tl_id'];

    if(isset($user_id) && isset($usertype) && isset($tl_id))
    {
        try
        {
            $con = connect_db();

            $con->beginTransaction();

            //Prepare a Query Statement
            if($usertype === "Teamleader" || $usertype === "Head")
            {
                $sql = "UPDATE `vn_users` SET `tl_id`= :tl_id WHERE `tl_id` = :user_id";
                
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':tl_id', $tl_id, PDO::PARAM_INT);

                if ($stmt->execute()) 
                {
                   $sql = "UPDATE `vn_users` SET `active`= :active WHERE `user_id` = :user_id";
                }
                else
                {
                    throw new PDOException('Can not deactivate the user');
                }
            }  
            else
            {
                $sql = "UPDATE `vn_users` SET `active`= :active WHERE `user_id` = :user_id";
            } 
            $stmt = $con->prepare($sql);
            $active = 0;
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':active', $active, PDO::PARAM_INT);

            if ($stmt->execute()) 
            {
                $result['result'] = "success";
                $count = $stmt->rowCount();
                if($count == 1) 
                {
                    $con->commit();
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($result));
                } 
                else 
                {
                    $con->rollBack();
                    throw new PDOException('Can not deactivate the user');
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