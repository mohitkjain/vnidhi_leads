<?php

$app->get('/api/users', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';
    try
    {
        $con = connect_db();

        //Prepare a Query Statement
        $stmt = $con->prepare("SELECT user.user_id, user.fname, user.lname, FROM_BASE64(user.loginid) AS loginid, user.password, user.usertype, user.empid, user.position, user.tl_id, user.active, CONCAT(tl.fname, ' ', tl.lname) AS 'tl_name' FROM vn_users user INNER JOIN vn_users tl ON user.tl_id= tl.user_id WHERE user.usertype NOT IN ('Admin') ");
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
});