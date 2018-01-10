<?php

class Get_Scheme_Info
{
    public $months;
    public $interset_rate_amount;
}

$app->get('/api/admin/schemes/{scheme_id}/{rate_exist}', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    $scheme_id = $request->getAttribute('scheme_id');
    $rate_exist = $request->getAttribute('rate_exist');
    try
    {
        if(isset($scheme_id) && isset($rate_exist))
        {
            $con = connect_db();
            $sql = '';
            if($rate_exist == 1)
            {
                $sql = "SELECT `time` AS 'months', `interset_rate` AS 'interset_rate_amount' FROM `vn_scheme_with_rate` WHERE `scheme_id` = :scheme_id";
            }
            else
            {
                $sql = "SELECT `time` AS 'months', `amount` AS 'interset_rate_amount' FROM `vn_scheme_without_rate` WHERE `scheme_id` = :scheme_id";
            }     
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':scheme_id', $scheme_id);
            if($stmt->execute())
            {
                $result = $stmt->fetchAll(PDO::FETCH_CLASS, "Get_Scheme_Info");
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
        else
        {
            throw new PDOException('Please Provide all parameters.');
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