<?php

class SchemeDetails
{
    public $scheme_id = "";
    public $scheme_type = "";
    public $minimum_amount = "";
    public $multiple_amount = "";
    public $rate_exist = "";
    public $time_interest = array();
}

$app->get('/api/schemes/{scheme_id}', function ($request, $response)
{
    require_once 'settings/dbconnect.php';

    $scheme_id = $request->getAttribute('scheme_id');
    if(isset($scheme_id))
    {
        try
        {
            $con = connect_db();
    
            //Prepare a Query Statement
            $stmt = $con->prepare("SELECT `scheme_id`,`scheme_type`, `min_value`,`rate_exists`,`value_per` FROM `vn_scheme_description` WHERE scheme_id = :scheme_id");
            $stmt->bindParam(':scheme_id', $scheme_id, PDO::PARAM_INT);
            if ($stmt->execute()) 
            {
                $data = $stmt->fetch();

                if($stmt->rowCount() == 1)
                {
                    $scheme_id = $data["scheme_id"];
                    $scheme_type = $data["scheme_type"];
                    $min_value = $data["min_value"];
                    $value_per = $data["value_per"];
                    $rate_exists = $data["rate_exists"];
                    $time_interest = array();

                    if($rate_exists == 1)
                    {
                        $stmt2 = $con->prepare("SELECT `time` AS 'month', `interset_rate` FROM `vn_scheme_with_rate` WHERE scheme_id = :scheme_id");
                        $stmt2->bindParam(':scheme_id', $scheme_id, PDO::PARAM_INT);

                        if($stmt2->execute())
                        {
                            $time_interest = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                        }
                    }
                    else
                    {
                        $stmt2 = $con->prepare("SELECT `time`, amount FROM `vn_scheme_without_rate` WHERE scheme_id = :scheme_id");
                        $stmt2->bindParam(':scheme_id', $scheme_id, PDO::PARAM_INT);

                        if($stmt2->execute())
                        {
                            $data2 = $stmt2->fetch();

                            if($stmt2->rowCount() == 1)
                            {
                                $month = $data2['time'];
                                if($scheme_id == 8)
                                {
                                    $interset_rate = '1.5x';
                                }
                                else
                                {
                                    $interset_rate = 'N.A.';
                                }
                            }
                            $time_interest[0]['month'] =  $month;
                            $time_interest[0]['interset_rate'] =  $interset_rate;
                            $time_interest[0]['amount'] = $data2['amount'];
                        }
                    }
    
                    $scheme_details = new SchemeDetails();
                    
                    $scheme_details->scheme_id = $scheme_id;
                    $scheme_details->minimum_amount = $min_value;
                    $scheme_details->multiple_amount = $value_per;
                    $scheme_details->scheme_type = $scheme_type;
                    $scheme_details->rate_exist = $rate_exists;                    
                    $scheme_details->time_interest = $time_interest;
                    
                    if($scheme_details) 
                    {
                        return $response->withStatus(200)
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode($scheme_details));
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
    }
});
