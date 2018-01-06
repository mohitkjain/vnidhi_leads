<?php

class GetTarget
{
    public $user_id;
    public $pre_month;
    public $pre_year;
    public $pre_month_target = 0;
    public $pre_month_achieved = 0;
}

$app->get('/api/admin/target/previous_month/{user_id}', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';

    $user_id = $request->getAttribute('user_id');

    if(isset($user_id))
    {
        try
        {
            $con = connect_db();

            $current_date = date('Y-m-d');
            $pre_month = date("m", strtotime($current_date . " last month"));
            $pre_year = date("Y", strtotime($current_date . " last month"));

            $sql = "SELECT `target_amount`, achieved FROM `vn_target_fd` WHERE user_id = :user_id AND target_month = :pre_month AND target_year = :pre_year";

            $stmt = $con->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':pre_month', $pre_month, PDO::PARAM_INT);
            $stmt->bindParam(':pre_year', $pre_year, PDO::PARAM_INT);

            if($stmt->execute())
            {
                $pre_month_target; $pre_month_achieved;
                $target_data = $stmt->fetch();
                if(!empty($target_data['target_amount']))
                {
                    $pre_month_target = $target_data['target_amount'];
                }
                else
                {
                    $pre_month_target = "0";
                }
                if(!empty($target_data['achieved']))
                {
                        $pre_month_achieved = $target_data['achieved'];
                }
                else
                {
                        $pre_month_achieved = "0";
                }
                $result = new GetTarget();
                $result->user_id = $user_id;
                $result->pre_month = $pre_month;
                $result->pre_year = $pre_year;
                $result->pre_month_target = $pre_month_target;
                $result->pre_month_achieved = $pre_month_achieved;
                if($result) 
                {
                    return $response->withStatus(200)
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode($result));
                }  
                else 
                {
                    throw new PDOException('No Record Found');
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
    }
});