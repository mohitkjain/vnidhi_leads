<?php

$app->post('/api/users/request/assignee', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $userid = $parsedBody['userid'];
    $assignee_id = $parsedBody['assignee_id'];
    $usertype = $parsedBody['usertype'];

    if(isset($userid) && isset($usertype))
    {
        try
        {
            if($usertype === 'Teamleader')
            {
                $con = connect_db();

                //Prepare a Query Statement
                $stmt = $con->prepare("SELECT user_id, fname, lname FROM vn_users WHERE (tl_id = :userid AND usertype = :_type AND user_id != :assignee_id AND active = 1)");
                $_type = "Salaried";

                $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
                $stmt->bindParam(':assignee_id', $assignee_id, PDO::PARAM_INT);
                $stmt->bindParam(':_type', $_type, PDO::PARAM_STR);

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
            else
            {
                throw new PDOException('Only Teamleader change Assignee');
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