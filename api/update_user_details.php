<?php

$app->post('/api/users/update', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $user_id = $parsedBody['user_id'];
    $email_id = $parsedBody['email_id'];
    $mobile = $parsedBody['mobile'];
    $dob = $parsedBody['dob'];

    if(isset($user_id) && isset($email_id) && isset($mobile) && isset($dob))
    {
        try
        {
            $con = connect_db();

            //Prepare a Query Statement
            $sql = "SELECT `user_id` FROM `vn_userinfo` WHERE `user_id` = :user_id";
            $stmt = $con->prepare($sql);

            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($stmt->execute()) 
            {
                $count = $stmt->rowCount();
                $result = array();
                if($count == 1)
                {
                    //Prepare a Query Statement
                    $sql = "UPDATE `vn_userinfo` SET `email_id`= :email_id, `mobile`= :mobile, `dob`= :dob WHERE `user_id` = :user_id";
                    $stmt = $con->prepare($sql);

                    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindParam(':email_id', $email_id, PDO::PARAM_STR);
                    $stmt->bindParam(':mobile', $mobile);
                    $stmt->bindParam(':dob', $dob, PDO::PARAM_STR);

                    if($stmt->execute())
                    {
                        $count = $stmt->rowCount();
                        if($count == 1)
                        {
                            $result['result'] = "success";
                        }
                        else
                        {
                            throw new PDOException('Can not update the info');
                        }
                    }
                }
                else if($count == 0)
                {
                    //Prepare a Query Statement
                    $sql = "INSERT INTO `vn_userinfo`(`user_id`, `email_id`, `mobile`, `dob`) VALUES (:user_id, :email_id, :mobile, :dob)";
                    $stmt = $con->prepare($sql);

                    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindParam(':email_id', $email_id, PDO::PARAM_STR);
                    $stmt->bindParam(':mobile', $mobile);
                    $stmt->bindParam(':dob', $dob, PDO::PARAM_STR);

                    if($stmt->execute())
                    {
                        $count = $stmt->rowCount();
                        if($count == 1)
                        {
                            $result['result'] = "success";
                        }
                        else
                        {
                            throw new PDOException('Can not insert the info');
                        }
                    }
                }            
                if(isset($result['result']))
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($result));
                }
                else 
                { 
                    throw new PDOException('Can not insert/update the info');
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