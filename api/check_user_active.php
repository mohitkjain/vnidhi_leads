<?php


$app->get('/api/users/check/active/{user_id}', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $user_id = $request->getAttribute('user_id');

    if(isset($user_id))
    {
        try
        {
            $con = connect_db();

            //Prepare a Query Statement
            $stmt = $con->prepare("SELECT active FROM `vn_users` WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($stmt->execute()) 
            {
                $data = $stmt->fetch();    
                if ($stmt->rowCount() == 1)
                {
                    $active = $data["active"];
                    $flag = "false";
                    if($active == 1)
                    {
                        $flag = "true";
                    }

                   $result['flag'] = $flag;
    
                    if($result) 
                    {
                        return $response->withStatus(200)
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode($result));
                    } 
                    else 
                    { 
                        throw new PDOException('User Id not exist');
                    }
                }
                else
                {
                    $errors = array();
                    $errors['flag'] = "false";
                    header('Content-Type: application/json');
                    echo json_encode($errors, JSON_FORCE_OBJECT);
                    //throw new PDOException('User Id not exist');
                }                
            }           
        }
        catch(PDOException $e)
        {
            $errors = array();
            $errors['flag'] = "false";
            $errors['error_msg'] = $e->getMessage();
            return $response->withStatus(404)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($errors));
        }
       
    }
});