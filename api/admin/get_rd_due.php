<?php

class Get_Due_RD_Details
{
    public $lead_id;
    public $installment_no;
    public $due_date;
    public $payment_status;
    public $c_name;
    public $creator_id;
    public $creator_name;
    public $assignee_id;
    public $assignee_name;
    public $duration;
    public $amount;
    public $closing_date;
}

$app->get('/api/admin/rd/due', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    try
    {
        $last_day_this_month  = date('Y-m-t');
        $con = connect_db();

        $sql = "SELECT rd.`lead_id`, rd.`installment_no`, rd.date AS 'due_date', rd.payment_status, lead.c_name, lead.creator_id, CONCAT(creator.fname, ' ', creator.lname) AS 'creator_name', lead.assignee_id, CONCAT(assignee.fname, ' ', assignee.lname) AS 'assignee_name', leadinfo.duration, leadinfo.amount, lead.closing_date FROM `vn_rd_reward_incentive` rd INNER JOIN vn_leads lead ON rd.lead_id = lead.lead_id INNER JOIN vn_users creator ON lead.creator_id = creator.user_id INNER JOIN vn_users assignee ON lead.creator_id = assignee.user_id INNER JOIN vn_lead_info leadinfo ON rd.lead_id = leadinfo.lead_id WHERE payment_status = 'unpaid' AND date <= :last_day_this_month";

        $stmt = $con->prepare($sql);
        $stmt->bindParam(':last_day_this_month', $last_day_this_month);

        if($stmt->execute())
        {
            $result = $stmt->fetchAll(PDO::FETCH_CLASS, "Get_Due_RD_Details");
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
        else 
        {
            throw new PDOException('No Record Found');
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