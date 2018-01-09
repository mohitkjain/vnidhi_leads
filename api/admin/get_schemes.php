<?php

class Get_Scheme_Details
{
    public $scheme_id;
    public $scheme_name;
    public $scheme_type;
    public $minimum_amount;
    public $rate_exists;
    public $multiple_amount;
}

$app->get('/api/admin/schemes', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    try
    {
        $con = connect_db();

        $sql = "SELECT `scheme_id`, `scheme_name`, `scheme_type`, `min_value` AS 'minimum_amount', `rate_exists`, `value_per` AS 'multiple_amount'  
        FROM `vn_scheme_description`";

        $stmt = $con->prepare($sql);

        if($stmt->execute())
        {
            $result = $stmt->fetchAll(PDO::FETCH_CLASS, "Get_Scheme_Details");
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