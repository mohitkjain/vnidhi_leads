<?php

$app->get('/api/leads/comments/{lead_id}', function ($request, $response)
{
    require_once 'settings/dbconnect.php';

    $lead_id = $request->getAttribute('lead_id');
    
    if(isset($lead_id))
    {
        try
        {
            $con = connect_db();

            //Prepare a Query Statement
            $stmt = $con->prepare("SELECT `commentator_id`, `comment`, `comment_date`, CONCAT(user.fname, ' ', user.lname) AS 'commentator_name' FROM `vn_comments` comment_table INNER JOIN vn_users user ON comment_table.`commentator_id`= user.user_id WHERE lead_id = :lead_id");

            $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
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
            echo '{"error" : {"text":'. $e->getMessage() .'}}';
            exit;
        }       
    }
});
