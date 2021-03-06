<?php

$app->get('/api/admin/active_leads', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    try
    {
        $con = connect_db();

        //Prepare a Query Statement
        $sql = "SELECT `lead_id`, `c_name`, `date_created`, CONCAT(assignee.fname, ' ', assignee.lname) AS 'assignee_name', CONCAT(creator.fname, ' ', creator.lname) AS 'creator_name', (CASE `status` WHEN 'telecalling_done' THEN 'Telecalling Done' WHEN 'home_meeting' THEN 'Home Meeting' WHEN 'follow_up' THEN 'Follow Up' WHEN 'request_pending' THEN 'Request Pending' END) AS 'status' FROM `vn_leads` leads INNER JOIN vn_users assignee ON leads.`assignee_id` = assignee.user_id INNER JOIN vn_users creator ON leads.`creator_id` = creator.user_id WHERE `status` IN ('telecalling_done', 'home_meeting', 'follow_up', 'request_pending')";
        $stmt = $con->prepare($sql);
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