<?php

$app->post('/api/telecaller/incentive', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $lead_id = $parsedBody['lead_id'];
    $creator_id = $parsedBody['creator_id'];
    $duration = $parsedBody['duration'];
    $amount = $parsedBody['amount'];
    $date = $parsedBody['date'];
    $scheme_type = $parsedBody['scheme_type'];
    $lead_type = $parsedBody['lead_type'];

    $result;

    if(isset($lead_id) && isset($creator_id) && isset($duration) && isset($amount) && isset($scheme_type) && isset($lead_type) && isset($date))
    {
        try
        {
            $con = connect_db();
            if($lead_type === "company_lead")
            {                
                if($scheme_type === "RD")
                {
                    $duration = ceil($duration / 12);
                }
                $sql = "SELECT `incentive`, `multiple_value` FROM `vn_incentive_telecaller` WHERE `scheme_type` = :scheme_type && duration = :duration";

                $stmt = $con->prepare($sql);
                $stmt->bindParam(':scheme_type', $scheme_type, PDO::PARAM_STR);
                $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);

                if ($stmt->execute()) 
                {
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    $incentive_rupee = $data['incentive'];
                    $multiple_value = $data['multiple_value'];

                    $multiple_count = floor($amount / $multiple_value);
                    $telecaller_incentive =  $incentive_rupee * $multiple_count;

                    $sql = "INSERT INTO `vn_incentive_earn_telecaller`(`lead_id`, `scheme_type`, `date`, `telecaller_id`, `telecaller_incentive`) VALUES (:lead_id, :scheme_type, :date, :telecaller_id, :telecaller_incentive)";

                    $stmt = $con->prepare($sql);
                    $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
                    $stmt->bindParam(':scheme_type', $scheme_type, PDO::PARAM_STR);
                    $stmt->bindParam(':date', $date);
                    $stmt->bindParam(':telecaller_id', $creator_id, PDO::PARAM_INT);
                    $stmt->bindParam(':telecaller_incentive', $telecaller_incentive);

                    if ($stmt->execute()) 
                    {
                        $id = $con->lastInsertId();
                        if($id >= 1)
                        {
                            $result['result'] = "success";
                        }
                        else
                        {
                            throw new PDOException('Can not add Telecaller incentive.');
                        }
                    }
                }
            }
            if($result)
            {
                return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($result));
            }
            else
            {
                throw new PDOException('Can not add Telecaller incentive.');
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