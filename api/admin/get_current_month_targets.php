<?php

class GetCurrentMonthTarget
{
    public $user_id;
    public $current_month;
    public $current_year;
    public $current_month_target = 0;
    public $current_month_achieved = 0;
}

$app->get('/api/admin/target/current_month/{user_id}', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';

    $user_id = $request->getAttribute('user_id');
    //$parsedBody = $request->getParsedBody();
    //$user_id = $parsedBody['user_id'];
    //$current_month = $parsedBody['current_month'];
    //$current_year = $parsedBody['current_year'];

    if(isset($user_id))
    {
        try
        {
            $current_month = date('m');
            $current_year = date('Y');
            $con = connect_db();

            $sql = "SELECT `target_amount` FROM `vn_target_fd` WHERE user_id = :user_id AND target_month = :current_month AND target_year = :current_year";

            $stmt = $con->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':current_month', $current_month, PDO::PARAM_INT);
            $stmt->bindParam(':current_year', $current_year, PDO::PARAM_INT);

            if($stmt->execute())
            {
                $current_month_target; $current_month_achieved;
                $target_data = $stmt->fetch();
                if(isset($target_data['target_amount']))
                {
                    $current_month_target = $target_data['target_amount'];
                }
                else
                {
                    $current_month_target = "0";
                }

                $sql = "SELECT achieved  FROM vn_target_fd_achieved WHERE user_id = :user_id AND target_month = :current_month AND target_year = :current_year";

                $stmt = $con->prepare($sql);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':current_month', $current_month, PDO::PARAM_INT);
                $stmt->bindParam(':current_year', $current_year, PDO::PARAM_INT);

                if ($stmt->execute()) 
                {
                    $target_data = $stmt->fetch();
                    if(isset($target_data['achieved']))
                    {
                        $current_month_achieved = $target_data['achieved'];
                    }
                    else
                    {
                        $current_month_achieved = "0";
                    }
                    $result = new GetCurrentMonthTarget();
                    $result->user_id = $user_id;
                    $result->current_month = $current_month;
                    $result->current_year = $current_year;
                    $result->current_month_target = $current_month_target;
                    $result->current_month_achieved = $current_month_achieved;
                    if($result) 
                    {
                        return $response->withStatus(200)
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode($result));
                    } 
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