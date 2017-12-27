<?php

class SetCurrentMonthTarget
{
    public $result;
    public $user_id;
    public $current_month;
    public $current_year;
    public $current_month_target = 0;
}

$app->post('/api/admin/set_target/current_month', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';

    //$user_id = $request->getAttribute('user_id');
    $parsedBody = $request->getParsedBody();
    $user_id = $parsedBody['user_id'];
    $target_amount = $parsedBody['target_amount'];
    //$current_month = $parsedBody['current_month'];
    //$current_year = $parsedBody['current_year'];

    if(isset($user_id))
    {
        try
        {
            $target_month = date('m');
            $target_year = date('Y');
            $con = connect_db();

            $sql = "SELECT COUNT(*) AS 'total_rows' FROM `vn_target_fd` WHERE user_id = :user_id AND target_month = :target_month AND target_year = :target_year";

            $stmt = $con->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':target_month', $target_month, PDO::PARAM_INT);
            $stmt->bindParam(':target_year', $target_year, PDO::PARAM_INT);

            if($stmt->execute())
            {
                $data = $stmt->fetch();
                $row = $data['total_rows'];
                if($row >= 1)
                {
                    $sql = "UPDATE `vn_target_fd` SET `target_amount`= :target_amount WHERE `user_id` = :user_id AND `target_year`= :target_year AND `target_month`= :target_month";
                }
                else
                {
                    $sql = "INSERT INTO `vn_target_fd`(`user_id`, `target_amount`, `target_year`, `target_month`) VALUES (:user_id, :target_amount, :target_year, :target_month)";
                }
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':target_amount', $target_amount, PDO::PARAM_INT);
                $stmt->bindParam(':target_month', $target_month, PDO::PARAM_INT);
                $stmt->bindParam(':target_year', $target_year, PDO::PARAM_INT);

                if ($stmt->execute()) 
                {              
                    $result = new SetCurrentMonthTarget();

                    $result->result = "success";
                    $result->user_id = $user_id;
                    $result->current_month = $target_month;
                    $result->current_year = $target_year;
                    $result->current_month_target = $target_amount;                    
                
                    if($result) 
                    {
                        return $response->withStatus(200)
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode($result));
                    } 
                }     
                else 
                {
                    throw new PDOException('Insertion not allowed');
                }     
            }
            else 
            {
                throw new PDOException('Insertion not allowed');
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