<?php

class Top_Salaried
{
    public $user_id;
    public $user_name;
    public $total_business;
    public $total_leads;
}

$app->get('/api/admin/top_performer/Salaried', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    try
    {
        $con = connect_db();

        //Prepare a Query Statement
        $sql = "SELECT leads.`assignee_id` AS 'user_id', CONCAT(assignee.fname, ' ', assignee.lname) AS 'user_name', SUM(info.`amount`) AS 'total_business', COUNT(*) AS 'total_leads' 
                FROM `vn_leads` leads
                INNER JOIN `vn_lead_info` info ON leads.`lead_id` = info.`lead_id`
                INNER JOIN `vn_users` assignee ON leads.`assignee_id` = assignee.`user_id`
                WHERE `status` = 'Accepted' 
                AND assignee.usertype = 'Salaried'
                AND MONTH(`closing_date`) = MONTH(CURRENT_DATE) 
                GROUP BY leads.`assignee_id`
                ORDER BY total_business DESC";
        $stmt = $con->prepare($sql);
        if ($stmt->execute()) 
        {
            $data = $stmt->fetchALL(PDO::FETCH_CLASS, "Top_Salaried");
            
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