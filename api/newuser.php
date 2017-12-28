<?php

class NewUser 
{
    public $result = "";
    public $userid = "";
}

$app->post('/api/users/user_add', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $fname = $parsedBody['fname'];
    $lname = $parsedBody['lname'];
    $login = $parsedBody['login'];
    $pass = $parsedBody['password'];
    $usertype = $parsedBody['usertype'];
    $position = $parsedBody['position'];
    $empid = $parsedBody['empid'];
    $tl_id = $parsedBody['tl_id'];

    if(isset($fname) && isset($lname) && isset($login) && isset($pass) && isset($usertype) && isset($position) && isset($empid) && isset($tl_id))
    {
        try
        {
            $con = connect_db();
            
            $login = base64_encode($login);
            $pass = base64_encode($pass);

            //Prepare a Query Statement
            $stmt = $con->prepare("INSERT INTO `vn_users` (`fname`, `lname`, `loginid`, `password`, `usertype`, `empid`, `position`, `tl_id`) VALUES (:fname, :lname, :loginid, :password, :usertype, :empid, :position, :tl_id)");
            $stmt->bindParam(':fname', $fname, PDO::PARAM_STR);
            $stmt->bindParam(':lname', $lname, PDO::PARAM_STR);
            $stmt->bindParam(':loginid', $login, PDO::PARAM_STR);
            $stmt->bindParam(':password', $pass, PDO::PARAM_STR);
            $stmt->bindParam(':usertype', $usertype, PDO::PARAM_STR);
            $stmt->bindParam(':position', $position, PDO::PARAM_STR);
            $stmt->bindParam(':empid', $empid, PDO::PARAM_INT);
            $stmt->bindParam(':tl_id', $tl_id, PDO::PARAM_INT);

            //Execute a query statement
            if ($stmt->execute()) 
            {
                $result = "success";
                $stmt = $con->prepare("SELECT user_id FROM `vn_users` WHERE loginid=:loginid");
                $stmt->bindValue(':loginid', $login, PDO::PARAM_STR);
                if ($stmt->execute()) 
                {
                    $data = $stmt->fetch();
                    $user_id = $data["user_id"];
                }                
            } 
            else 
            {
                $result = "failure";
                $user_id = "";
            }

            $user = new NewUser();
            $user->result = $result;
            $user->userid = $user_id;

            if($user) 
            {
                return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($user));
            } 
            else 
            { 
                throw new PDOException('Can not insert a user');
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