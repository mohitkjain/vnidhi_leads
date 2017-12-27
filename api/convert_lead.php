<?php

class ConvertLead 
{
    public $result = "";
}

$app->post('/api/leads/convert', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $lead_id = $parsedBody['lead_id'];
    $user_id = $parsedBody['user_id'];
    $scheme_id = $parsedBody['scheme_id'];
    $duration = $parsedBody['duration'];
    $aadhar_no = $parsedBody['aadhar_no'];
    $pan_card = $parsedBody['pan_card'];
    $amount = $parsedBody['amount'];
    $others = $parsedBody['others'];

    $lead_type = "company_lead";
    $result = "";

    if(isset($lead_id) && isset($user_id) && isset($scheme_id) && isset($duration) && isset($aadhar_no) && isset($pan_card) && isset($amount) && isset($others))
    {
        try
        {
            $con = connect_db();
            $sql = "INSERT INTO `vn_lead_info` (`user_id`, `lead_id`, `lead_type`, `scheme_id`, `duration`, `amount`, `aadhar_no`, `pan_card`, `others`) VALUES (:user_id, :lead_id, :lead_type, :scheme_id, :duration, :amount, :aadhar_no, :pan_card, :others)";
            $stmt = $con->prepare($sql);

            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
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
                $result = "success";
            }
            $lead = new ConvertLead();
            $lead->result = $result;
            return $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($lead));
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