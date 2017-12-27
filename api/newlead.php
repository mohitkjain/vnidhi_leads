<?php

class NewLead 
{
    public $result = "";
}

$app->post('/api/leads/add', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $lead_type = $parsedBody['lead_type'];
    $c_name = $parsedBody['c_name'];
    $c_address = $parsedBody['c_address'];
    $c_mobile = $parsedBody['c_mobile'];
    $c_email = $parsedBody['c_email'];
    $description = $parsedBody['description'];
    $assignee_id = $parsedBody['assignee_id'];
    $creator_id = $parsedBody['creator_id'];

    if(isset($lead_type) && isset($c_name) && isset($c_address) && isset($c_mobile) && isset($description) && isset($assignee_id) && isset($creator_id))
    {
        try
        {
            $con = connect_db();
            
            //Prepare a Query Statement
            $stmt = $con->prepare("INSERT INTO `vn_leads` (`lead_type`, `c_name`, `c_address`, `c_mobile`, `c_email`, `description`, `status`, `assignee_id`, `creator_id`, `date_created`) VALUES (:lead_type, :c_name, :c_address, :c_mobile, :c_email, :description, :status, :assignee_id, :creator_id, :date)");
            $status = "telecalling_done";
            $date = date('Y-m-d');
            
            $stmt->bindParam(':lead_type', $lead_type, PDO::PARAM_STR);
            $stmt->bindParam(':c_name', $c_name, PDO::PARAM_STR);
            $stmt->bindParam(':c_address', $c_address, PDO::PARAM_STR);
            $stmt->bindParam(':c_mobile', $c_mobile, PDO::PARAM_STR);
            $stmt->bindParam(':c_email', $c_email, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':assignee_id', $assignee_id, PDO::PARAM_INT);
            $stmt->bindParam(':creator_id', $creator_id, PDO::PARAM_INT);
            $stmt->bindParam(':date', $date, PDO::PARAM_STR);

            //Execute a query statement
            if($stmt->execute()) 
            {
                $result = "success";       
            } 
            else 
            {
                $result = "failure";
            }

            $lead = new NewLead();
            $lead->result = $result;

            if($lead) 
            {
                return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($lead));
            } 
            else 
            { 
                throw new PDOException('Can not insert data.');
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