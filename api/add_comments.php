<?php

class NewComment 
{
    public $result = "";
}

$app->post('/api/leads/comments/add', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $commentator_id = $parsedBody['commentator_id'];
    $lead_id = $parsedBody['lead_id'];
    $comment = $parsedBody['comment'];

    if(isset($commentator_id) && isset($lead_id) && isset($comment))
    {
        try
        {
            $con = connect_db();
            
            //Prepare a Query Statement
            $stmt = $con->prepare("INSERT INTO `vn_comments` (`commentator_id`, `lead_id`, `comment`, `comment_date`) VALUES (:commentator_id, :lead_id, :comment, :date)");
            $date = date('Y-m-d');
            
            $stmt->bindParam(':commentator_id', $commentator_id, PDO::PARAM_INT);
            $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
            $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
            $stmt->bindParam(':date', $date, PDO::PARAM_STR);

            //Execute a query statement
            if($stmt->execute()) 
            {
                $result = "success";       
            } 
            else 
            {
                $result = "failure";
            }

            $comment_obj = new NewComment();
            $comment_obj->result = $result;

            if($comment_obj) 
            {
                return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($comment_obj));
            } 
            else 
            { 
                throw new PDOException('Can not insert comment.');
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