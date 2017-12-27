<?php

class DirectLead 
{
    public $result = "";
}

$app->post('/api/leads/direct', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $lead_type = $parsedBody['lead_type'];
    $c_name = $parsedBody['c_name'];
    $c_address = $parsedBody['c_address'];
    $c_mobile = $parsedBody['c_mobile'];
    $c_email = $parsedBody['c_email'];
    $description = $parsedBody['description'];
    $assignee_id = $parsedBody['creator_id'];
    $scheme_id = $parsedBody['scheme_id'];
    $duration = $parsedBody['duration'];
    $aadhar_no = $parsedBody['aadhar_no'];
    $pan_card = $parsedBody['pan_card'];
    $amount = $parsedBody['amount'];
    $others = $parsedBody['others'];

    $creator_id = $parsedBody['creator_id'];
    $result = "";

    if(isset($lead_type) && isset($c_name) && isset($c_address) && isset($c_mobile) && isset($description) && isset($assignee_id) && isset($creator_id) && isset($scheme_id) && isset($duration) && isset($aadhar_no) && isset($others))
    {
        try
        {
            $con = connect_db();
            
            $lead = new DirectLead();
            //We start our transaction.
            $con->beginTransaction();

            //We will need to wrap our queries inside a TRY / CATCH block.
            //That way, we can rollback the transaction if a query fails and a PDO exception occurs.
            try
            {

                //Query 1: Attempt to insert the lead record into lead table.
                $sql = "INSERT INTO `vn_leads` (`lead_type`, `c_name`, `c_address`, `c_mobile`, `c_email`, `description`, `status`, `assignee_id`, `creator_id`, `date_created`) VALUES (:lead_type, :c_name, :c_address, :c_mobile, :c_email, :description, :status, :assignee_id, :creator_id, :date)";
                
                $status = "request_pending";
                $date = date('Y-m-d');
                $lead_id = null;

                $stmt = $con->prepare($sql);
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

                if($stmt->execute())
                {                    
                    $lead_id = $con->lastInsertId();

                    //Query 2: Attempt to update the user's profile.
                    $sql = "INSERT INTO `vn_lead_info` (`user_id`, `lead_id`, `lead_type`, `scheme_id`, `duration`, `amount`, `aadhar_no`, `pan_card`, `others`) VALUES (:user_id, :lead_id, :lead_type, :scheme_id, :duration, :amount, :aadhar_no, :pan_card, :others)";
                    $stmt = $con->prepare($sql);

                    $stmt->bindParam(':user_id', $creator_id, PDO::PARAM_INT);
                    $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                    $stmt->bindParam(':lead_type', $lead_type, PDO::PARAM_STR);
                    $stmt->bindParam(':scheme_id', $scheme_id, PDO::PARAM_INT);
                    $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
                    $stmt->bindParam(':amount', $amount);
                    $stmt->bindParam(':aadhar_no', $aadhar_no);
                    $stmt->bindParam(':pan_card', $pan_card, PDO::PARAM_STR);
                    $stmt->bindParam(':others', $others, PDO::PARAM_STR);

                    if($stmt->execute())
                    {
                        //We've got this far without an exception, so commit the changes.
                        $con->commit();
                        $result = "success";
                    }
                    $lead->result = $result;
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($lead));
                }
            } 
            catch(Exception $e)
            {
                //An exception has occured, which means that one of our database queries failed.
                $errors = array();
                $errors[0]['result'] = "failure";
                $errors[0]['error_msg'] = $e->getMessage();
                //Rollback the transaction.
                $con->rollBack();
                
                $lead->result = $result;
                return $response->withStatus(404)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($lead));
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