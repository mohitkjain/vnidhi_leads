<?php

class Set_Redemption_Policy
{
    public $result;
}

$app->post('/api/admin/redemption/policy_data', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $id = $parsedBody['id'];
    $rewards_points = $parsedBody['rewards_points'];
    $reward = $parsedBody['reward'];

    if(isset($id) && isset($rewards_points) && isset($reward))
    {
        try
        {
            $con = connect_db();
             
            $sql_redemption = "UPDATE `vn_reedemption_data` SET `rewards_points`= :rewards_points, `reward`= :reward WHERE `id` = :id";
            
            $stmt_redemption = $con->prepare($sql_redemption);
            $stmt_redemption->bindParam(':id', $id);
            $stmt_redemption->bindParam(':rewards_points', $rewards_points);
            $stmt_redemption->bindParam(':reward', $reward);

            if ($stmt_redemption->execute()) 
            {
                $count = $stmt_redemption->rowCount();
                if($count > 0)
                {
                    $result = "success"; 
                }
                else
                {
                    $result = "failure";
                }       
                $obj = new Set_Redemption_Policy();
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