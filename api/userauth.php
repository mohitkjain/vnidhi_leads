<?php

class User 
{
    public $firstname = "";
    public $lastname  = "";
    public $userid = "";
    public $usertype = "";
    public $result ="";
}

class ErrorMessage
{
    public $result = "";
    public $errormessage = "";
}

$app->post('/api/users/auth', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $login = $parsedBody['login'];
    $pass = $parsedBody['password'];

    if(isset($login) && isset($pass))
    {
        try
        {
            $con = connect_db();

            //Prepare a Query Statement
            $stmt = $con->prepare("SELECT  user_id, fname, lname, usertype FROM `vn_users` WHERE loginid = :login && password = :pass AND active = 1");
            $stmt->bindParam(':login', $login, PDO::PARAM_STR);
            $stmt->bindParam(':pass', $pass, PDO::PARAM_STR);

            if ($stmt->execute()) 
            {
                $data = $stmt->fetch();    
                if ($stmt->rowCount() == 1)
                {
                    $user_id = $data["user_id"];
                    $fname = $data["fname"];
                    $lname = $data["lname"];
                    $usertype = $data["usertype"];

                    $user = new User();
                    $user->userid = $user_id;
                    $user->firstname = $fname;
                    $user->lastname  = $lname;
                    $user->usertype = $usertype;
                    $user->result = "success";
    
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
                else
                {
                    $error = new ErrorMessage();
                    $error->result = "failure";
                    $error->errormessage = "UserName or Password isn't correct.";
                    header('Content-type: application/json');
                    echo json_encode($error);
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