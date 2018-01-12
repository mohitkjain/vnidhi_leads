<?php

class Redemption_Policy
{
    public $id;
    public $rewards_points;
    public $reward;
}

$app->get('/api/admin/redemption/policy', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    
    $config = new config();
    try
    {
        $con = connect_db();        
       
        $sql_policy = "SELECT * FROM `vn_reedemption_data`";      
        $stmt_policy = $con->prepare($sql_policy);
       
        if ($stmt_policy->execute()) 
        {
            $reward_redemption_policy = $stmt_policy->fetchAll(PDO::FETCH_CLASS, "Redemption_Policy");
           
            if(isset($reward_redemption_policy)) 
            {
                return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($reward_redemption_policy));
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
});