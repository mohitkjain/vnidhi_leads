<?php

class UpdateLead 
{
    public $result = "";
}

$app->post('/api/leads/update', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $lead_id = $parsedBody['lead_id'];
    $c_name = $parsedBody['c_name'];
    $c_address = $parsedBody['c_address'];
    $c_mobile = $parsedBody['c_mobile'];
    $c_email = $parsedBody['c_email'];
    $description = $parsedBody['description'];
    $scheme_id = $parsedBody['scheme_id'];
    $duration = $parsedBody['duration'];
    $aadhar_no = $parsedBody['aadhar_no'];
    $pan_card = $parsedBody['pan_card'];
    $amount = $parsedBody['amount'];
    $others = $parsedBody['others'];

    $result = "";

    if(isset($c_name) && isset($c_address) && isset($c_mobile) && isset($description) &&  isset($scheme_id) && isset($duration) && isset($aadhar_no) && isset($amount))
    {
        try
        {
            $con = connect_db();
            
            $lead = new UpdateLead();
            $bool_leadinfo = false;
            $stmt_info = "";
           
            $sql = "UPDATE `vn_leads` SET `c_name`=:c_name,`c_address`=:c_address,`c_mobile`=:c_mobile,`c_email`=:c_email,`description`= :description WHERE lead_id = :lead_id";

            $stmt = $con->prepare($sql);
            $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
            $stmt->bindParam(':c_name', $c_name, PDO::PARAM_STR);
            $stmt->bindParam(':c_address', $c_address, PDO::PARAM_STR);
            $stmt->bindParam(':c_mobile', $c_mobile, PDO::PARAM_STR);
            $stmt->bindParam(':c_email', $c_email, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);

            if(!is_null($scheme_id) || !is_null($aadhar_no) || !is_null($duration) || !is_null($amount))
            {
                $bool_leadinfo = true;
                $sql_info = "UPDATE `vn_lead_info` SET `scheme_id`= :scheme_id,`duration`= :duration,`amount`= :amount,`aadhar_no`= :aadhar_no,`pan_card`= :pan_card,`others`= :others WHERE lead_id = :lead_id"; 

                $stmt_info = $con->prepare($sql_info);
                
                $stmt_info->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                $stmt_info->bindParam(':scheme_id', $scheme_id, PDO::PARAM_INT);
                $stmt_info->bindParam(':duration', $duration, PDO::PARAM_INT);
                $stmt_info->bindParam(':amount', $amount);
                $stmt_info->bindParam(':aadhar_no', $aadhar_no);
                $stmt_info->bindParam(':pan_card', $pan_card, PDO::PARAM_STR);
                $stmt_info->bindParam(':others', $others, PDO::PARAM_STR);
            }

            if($bool_leadinfo)
            {
                if($stmt->execute() && $stmt_info->execute())
                {
                    $lead->result = "success";
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($lead)); 
                }
                else
                {
                    throw new PDOException('Can not udate data.');
                }
            }
            else
            {
                if($stmt->execute())
                {
                    $lead->result = "success";
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($lead));                
                }
                else
                {
                    throw new PDOException('Can not udate data.');
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