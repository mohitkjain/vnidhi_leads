<?php

class LeadInfo
{
    public $lead_id = null;
    public $lead_type = null;
    public $c_name = null;
    public $c_address = null;
    public $c_mobile = null;
    public $c_email = null;
    public $description = null;
    public $status =null;
    public $assignee_name = null;
    public $assignee_id = null;
    public $creator_name = null;
    public $converted = null;
    public $date_created = null;
    public $scheme_id = null;
    public $scheme_name = null;
    public $duration = null;
    public $amount = null;
    public $aadhar_no = null;
    public $pan_card = null;
    public $others = null;
    public $closing_date =null;
}

$app->get('/api/leads/info/{lead_id}', function ($request, $response)
{
    require_once 'settings/dbconnect.php';
    
    $lead_id = $request->getAttribute('lead_id');

    try
    {
        if(isset($lead_id))
        {
             $con = connect_db();

            //Prepare a Query Statement
            
            $stmt = $con->prepare("SELECT lead_id, lead_type, c_name, c_address, c_mobile, c_email, description, status, converted, date_created, closing_date, assignee_id, CONCAT(assignee.fname, ' ', assignee.lname) AS 'assignee_name', CONCAT(creator.fname, ' ', creator.lname) AS 'creator_name' FROM `vn_leads` lead INNER JOIN vn_users assignee ON lead.assignee_id = assignee.user_id INNER JOIN vn_users creator ON lead.creator_id = creator.user_id WHERE lead_id = :lead_id");
            $stmt->bindParam(':lead_id', $lead_id);

            if ($stmt->execute()) 
            {
                $lead_data = $stmt->fetch();
                $lead_info = new LeadInfo();

                $lead_info->lead_id = $lead_data['lead_id'];
                $lead_info->lead_type = $lead_data['lead_type'];
                $lead_info->c_name = $lead_data['c_name'];
                $lead_info->c_address = $lead_data['c_address'];
                $lead_info->c_mobile = $lead_data['c_mobile'];
                $lead_info->c_email = $lead_data['c_email'];
                $lead_info->description = $lead_data['description'];
                $lead_info->status = $lead_data['status'];
                $lead_info->assignee_id = $lead_data['assignee_id'];
                $lead_info->assignee_name = $lead_data['assignee_name'];
                $lead_info->creator_name = $lead_data['creator_name'];
                $lead_info->date_created = $lead_data['date_created'];
                $lead_info->converted = $lead_data['converted'];
                if(isset($lead_data['closing_date']))
                    $lead_info->closing_date = $lead_data['closing_date']; 

                $sql  = "SELECT lead_id FROM `vn_lead_info` WHERE lead_id = :lead_id";
                $chk_data = $con->prepare($sql);
                $chk_data->bindParam(':lead_id', $lead_id);

                if($chk_data->execute())
                {
                    $count = $chk_data->rowCount();
                    if($count == 1)
                    {
                        $stmt = $con->prepare("SELECT lead_type, info.scheme_id, scheme_desc.scheme_name, duration, amount, aadhar_no, pan_card, others, CONCAT(assignee.fname, ' ', assignee.lname) AS 'assignee_name' FROM `vn_lead_info` AS info INNER JOIN vn_users assignee ON info.user_id = assignee.user_id INNER JOIN vn_scheme_description scheme_desc ON info.scheme_id = scheme_desc.scheme_id WHERE lead_id = :lead_id");
                        $stmt->bindParam(':lead_id', $lead_id);
                        
                        if($stmt->execute())
                        {
                            $lead_converted_data = $stmt->fetch();
    
                            $lead_info->assignee_name = $lead_converted_data['assignee_name'];
                            $lead_info->lead_type = $lead_converted_data['lead_type'];
                            $lead_info->scheme_id = $lead_converted_data['scheme_id'];
                            $lead_info->scheme_name = $lead_converted_data['scheme_name'];
                            $lead_info->duration = $lead_converted_data['duration'];
                            $lead_info->amount = $lead_converted_data['amount'];
                            $lead_info->aadhar_no = $lead_converted_data['aadhar_no'];
                            $lead_info->pan_card = $lead_converted_data['pan_card'];
                            $lead_info->others = $lead_converted_data['others']; 
                        }   
                    }
                }
                
                if($lead_info) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($lead_info));
                } 
                else 
                { 
                    throw new PDOException('No records found');
                }
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
