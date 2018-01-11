<?php

class Rewards_Schemes
{
    public $id;
    public $user_type;
    public $lead_type;
    public $year_wise;
    public $reward_per;
}

$app->get('/api/admin/rewards/schemes', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    
    $config = new config();
    try
    {
        $con = connect_db();        
       
        $sql_schemes = "SELECT * FROM `vn_reward_table`";      
        $stmt_schemes = $con->prepare($sql_schemes);
       
        if ($stmt_schemes->execute()) 
        {
            $reward_schemes_data = $stmt_schemes->fetchAll(PDO::FETCH_CLASS, "Rewards_Schemes");
           
            if(isset($reward_schemes_data)) 
            {
                return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($reward_schemes_data));
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