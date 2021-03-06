<?php

$app->get('/api/teammates/{tl_id}', function ($request, $response)
{
    require_once 'settings/dbconnect.php';

    $tl_id = $request->getAttribute('tl_id');

    if(isset($tl_id))
    {
        try
        {
            $con = connect_db();

            //Prepare a Query Statement
            $stmt = $con->prepare("SELECT user_id, fname, lname FROM vn_users WHERE tl_id = :tl_id AND active = 1");

            $stmt->bindParam(':tl_id', $tl_id, PDO::PARAM_INT);
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
    }
});
