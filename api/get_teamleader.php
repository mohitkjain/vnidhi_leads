<?php

class TeamleaderInfo
{
    public $tl_id;
    public $tl_name;
}

$app->get('/api/teamleaders', function ($request, $response)
{
    require_once 'settings/dbconnect.php';
    $users = array("Telecaller", "Salaried", "Teamleader", "Head");
    
    try
    {
        $con = connect_db();

        $sql  = '';
        $tl_data = array();
        foreach($users as $user)
        {
            if($user === "Salaried" || $user === "Telecaller")
            {
                $sql = "SELECT user_id AS 'tl_id', CONCAT(fname, ' ', lname) AS 'tl_name' FROM vn_users WHERE usertype = 'Teamleader'";
            }               
            else if($user === "Teamleader")
            {
                $sql = "SELECT user_id AS 'tl_id', CONCAT(fname, ' ', lname) AS 'tl_name' FROM vn_users WHERE usertype = 'Head'";
            }
            else if($user === "Head")
            {
                $sql = "SELECT user_id AS 'tl_id', CONCAT(fname, ' ', lname) AS 'tl_name' FROM vn_users WHERE usertype = 'Admin'";
            }

            $stmt = $con->prepare($sql);

            if ($stmt->execute()) 
            {
                $result["$user"] = $stmt->fetchAll(PDO::FETCH_CLASS, "TeamleaderInfo");
            }
        }
        if($result) 
        {
            return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($result));
        } 
        else 
        { 
            throw new PDOException('No records found');
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
