<?php

class incentive_schemes
{
    public $no;
    public $schemeType;
    public $leadType;
    public $duration;
    public $incentive;
}

$app->get('/api/admin/incentive/schemes/{usertype}', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    
    $usertype = $request->getAttribute('usertype');
    $config = new config();

    if(isset($usertype))
    {
        try
        {
            $con = connect_db();
            $sql_schemes = "";
             
            if($usertype === "Telecaller")
            {
                $sql_schemes = "SELECT `scheme_type`, 'company_lead' AS 'lead_type', `duration`, `incentive` 
                FROM `vn_incentive_telecaller`";
            }
            else
            {
                $sql_schemes = "SELECT `scheme_type`, `lead_type`, `duration`, `incentive_per` AS 'incentive'
                FROM `vn_incentives` 
                WHERE `user_type` = :usertype";
            }
            
            $stmt_schemes = $con->prepare($sql_schemes);
            if($usertype != "Telecaller")
            {
                $stmt_schemes->bindParam(':usertype', $usertype);
            }

            if ($stmt_schemes->execute()) 
            {
                $incentive_schemes_data = $stmt_schemes->fetchAll(PDO::FETCH_ASSOC);
                $no = 1;
                $myArray = array();
                foreach ($incentive_schemes_data as $incentive_scheme) 
                {
                    $obj = new incentive_schemes();
                    $obj->no = $no;
                    $obj->schemeType = $incentive_scheme['scheme_type'];
                    $obj->leadType = $incentive_scheme['lead_type'];
                    if($incentive_scheme['scheme_type'] === 'FD')
                    {
                        $obj->duration = $incentive_scheme['duration']. " Months";
                    }
                    else
                    {
                        $obj->duration = $incentive_scheme['duration']. " Year";
                    }
                    if($usertype === 'Telecaller')
                    {
                        $obj->incentive = "₹". $incentive_scheme['incentive'] . ".00";
                    }
                    else
                    {
                        $obj->incentive = $incentive_scheme['incentive']. "%";
                    }
                    $myArray[] = $obj;      
                    $no++;              
                }
                if(count($myArray) > 0) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($myArray));
                } 
                else 
                { 
                    throw new PDOException('No Records Found.');
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