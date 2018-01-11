<?php

class Set_Rewards_Schemes
{
    public $result;
}

$app->post('/api/admin/rewards/change_data', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $id = $parsedBody['id'];
    $reward_per = $parsedBody['reward_per'];

    if(isset($id) && isset($reward_per))
    {
        try
        {
            $con = connect_db();
             
            $sql_scheme = "UPDATE `vn_reward_table` SET `reward_per`= :reward_per  WHERE `id` = :id";
            
            $stmt_scheme = $con->prepare($sql_scheme);
            $stmt_scheme->bindParam(':id', $id);
            $stmt_scheme->bindParam(':reward_per', $reward_per);

            if ($stmt_scheme->execute()) 
            {
                $count = $stmt_scheme->rowCount();
                if($count > 0)
                {
                    $result = "success"; 
                }
                else
                {
                    $result = "failure";
                }       
                $obj = new Set_Rewards_Schemes();
                $obj->result = $result;
                if($obj) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($obj));
                } 
                else 
                { 
                    throw new PDOException('Can not Update Record.');
                }
            } 
            else 
            { 
                throw new PDOException('Can not Update Record.');
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