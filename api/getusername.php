<?php

class UserName
{
    public $user_id = null;
    public $user_name = null;
}

$app->get('/api/users/name/{user_id}', function ($request, $response)
{
    require_once 'settings/dbconnect.php';

    $user_id = $request->getAttribute('user_id');

    if(isset($user_id))
    {
        try
        {
            $con = connect_db();
    
            //Prepare a Query Statement
            $stmt = $con->prepare("SELECT user_id, fname, lname FROM `vn_users` WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            if ($stmt->execute()) 
            {
                $data = $stmt->fetch();
                $user = new UserName();
                $user->user_id = $data['user_id'];
                $user->user_name = $data['fname']." ".$data['lname'];
                if($user) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($user));
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
