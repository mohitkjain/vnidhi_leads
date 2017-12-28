<?php

class EditUser 
{
    public $result;
}

$app->post('/api/admin/users/user_edit', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $fname = $parsedBody['fname'];
    $lname = $parsedBody['lname'];
    $login = $parsedBody['login'];
    $usertype = $parsedBody['usertype'];
    $position = $parsedBody['position'];
    $empid = $parsedBody['empid'];
    $tl_id = $parsedBody['tl_id'];
    $user_id =  $parsedBody['user_id'];

    if(isset($fname) && isset($lname) && isset($login) && isset($usertype) && isset($position) && isset($empid) && isset($tl_id)  && isset($user_id))
    {
        try
        {
            $con = connect_db();
            $result;
            $login = base64_encode($login);
            //Prepare a Query Statement
            $sql_update = "UPDATE `vn_users` SET `fname`= :fname,`lname`= :lname,`loginid`= :loginid,`usertype`= :usertype,`empid`= :empid,`position`= :position,`tl_id`= :tl_id WHERE `user_id` =  :user_id";
            $stmt = $con->prepare($sql_update);

            $stmt->bindParam(':fname', $fname, PDO::PARAM_STR);
            $stmt->bindParam(':lname', $lname, PDO::PARAM_STR);
            $stmt->bindParam(':loginid', $login, PDO::PARAM_STR);
            $stmt->bindParam(':usertype', $usertype, PDO::PARAM_STR);
            $stmt->bindParam(':position', $position, PDO::PARAM_STR);
            $stmt->bindParam(':empid', $empid, PDO::PARAM_INT);
            $stmt->bindParam(':tl_id', $tl_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            //Execute a query statement
            if ($stmt->execute()) 
            {
                $count = $stmt->rowCount();
                if($count == 1)
                {
                    $result = "success"; 
                }
                else
                {
                    $result = "failure";
                }       
            } 
            else 
            {
                $result = "failure";
            }

            $user = new EditUser();
            $user->result = $result;

            if($user) 
            {
                return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($user));
            } 
            else 
            { 
                throw new PDOException('Can not update a user');
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