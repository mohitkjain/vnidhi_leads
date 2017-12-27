<?php

$app->post('/api/users/fd/achieved', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $user_id = $parsedBody['user_id'];
    $current_month = $parsedBody['current_month'];
    $current_year = $parsedBody['current_year'];
    $amount = $parsedBody['amount'];

    if(isset($user_id) && isset($current_month) && isset($current_year) && isset($amount))
    {
        try
        {
            $con = connect_db();

            $con->beginTransaction();

            $sql = "SELECT `achieved` FROM `vn_target_fd_achieved` WHERE `user_id` = :user_id AND `target_month` = :current_month AND `target_year` = :current_year";
            
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':current_month', $current_month, PDO::PARAM_INT);
            $stmt->bindParam(':current_year', $current_year, PDO::PARAM_INT);

            if ($stmt->execute()) 
            {
                $count = $stmt->rowCount();
                if($count == 1)
                {
                    $data = $stmt->fetch();
                    $amount += $data['achieved'];

                    $sql = "UPDATE `vn_target_fd_achieved` SET `achieved` = :amount WHERE `user_id` = :user_id AND `target_month` = :current_month AND `target_year` = :current_year";
                }
                else
                {
                    $sql = "INSERT INTO `vn_target_fd_achieved`(`user_id`, `target_month`, `target_year`, `achieved`) VALUES (:user_id, :current_month, :current_year, :amount)";
                }

                $stmt = $con->prepare($sql);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':current_month', $current_month, PDO::PARAM_INT);
                $stmt->bindParam(':current_year', $current_year, PDO::PARAM_INT);
                $stmt->bindParam(':amount', $amount, PDO::PARAM_INT);

                if($stmt->execute())
                {
                    $result['result'] = "success";
                    $con->commit();
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($result));
                }
                else
                {
                    $con->rollBack();
                    throw new PDOException('Can not add into achieved fd table');
                }
            }
            else
            {
                throw new PDOException('Can not add into achieved fd table');
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