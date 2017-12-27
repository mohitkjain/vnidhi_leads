<?php

$app->get('/api/admin/leads_stats', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    try
    {
        $con = connect_db();

        //Prepare a Query Statement
        $sql = "SELECT COUNT(*) AS 'total_leads', (SELECT COUNT(*) FROM `vn_leads` WHERE `status` IN ('telecalling_done', 'home_meeting', 'follow_up', 'request_pending')) AS 'active_leads', (SELECT COUNT(*) FROM `vn_leads` WHERE `status` = 'accepted') AS 'accepted_leads', (SELECT COUNT(*) FROM `vn_leads` WHERE `status` = 'declined') AS 'declined_leads' FROM `vn_leads`";
        $stmt = $con->prepare($sql);
        if ($stmt->execute()) 
        {
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
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