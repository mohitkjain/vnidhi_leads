<?php

$app->get('/api/schemes', function ($request, $response)
{
    require_once 'settings/dbconnect.php';
    
    try
    {
        $con = connect_db();

        //Prepare a Query Statement
        $stmt = $con->prepare("SELECT scheme_id, scheme_name FROM `vn_scheme_description`");
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
