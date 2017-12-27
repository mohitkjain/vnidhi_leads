<?php

class UserInfo
{
    public $user_id = null;
    public $fname = null;
    public $lname = null;
    public $usertype = null;
    public $empid = null;
    public $position = null;
    public $tl_id = null;
    public $tl_name =null;
    public $email_id = null;
    public $mobile = null;
    public $dob = null;
    public $tot_accepted_leads = null;
    public $tot_declined_leads = null;
    public $tot_leads = null;
}

$app->get('/api/users/info/{user_id}', function ($request, $response)
{
    require_once 'settings/dbconnect.php';
    
    $user_id = $request->getAttribute('user_id');
    try
    {
        if(isset($user_id))
        {
             $con = connect_db();

            //Prepare a Query Statement
            $sql = "SELECT userdetail.`user_id`, userdetail.`fname`, userdetail.`lname`, userdetail.`usertype`, userdetail.`empid`, userdetail.`position`, userdetail.`tl_id`, CONCAT(teamleader.fname, ' ', teamleader.lname) AS 'tl_name', userinfo.email_id, userinfo.mobile, userinfo.dob FROM `vn_users` AS userdetail LEFT OUTER JOIN vn_userinfo AS userinfo ON userdetail.`user_id` = userinfo.user_id LEFT OUTER JOIN vn_users AS teamleader ON userdetail.`tl_id` = teamleader.user_id WHERE userdetail.`user_id` = :user_id";
            
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if ($stmt->execute()) 
            {
                $user_data = $stmt->fetch();
                $user_info = new UserInfo();

                $user_info->user_id = $user_data['user_id'];
                $user_info->fname = $user_data['fname'];
                $user_info->lname = $user_data['lname'];
                $user_info->usertype = $user_data['usertype'];
                $user_info->empid = $user_data['empid'];
                $user_info->position = $user_data['position'];
                $user_info->tl_id = $user_data['tl_id'];
                $user_info->tl_name = $user_data['tl_name'];
                $user_info->email_id = $user_data['email_id'];
                $user_info->mobile = $user_data['mobile'];
                $user_info->dob = $user_data['dob'];                
                
                $sql = "SELECT COUNT(lead_id) AS 'tot_leads', 
                SUM(CASE 
                    WHEN leads.`status` = 'accepted' THEN 1
                        ELSE 0
                    END) AS tot_accepted_leads,
                SUM(CASE 
                    WHEN leads.`status` = 'declined' THEN 1
                        ELSE 0
                    END) AS tot_declined_leads 
                FROM vn_leads leads
                WHERE `assignee_id` = :user_id";

                $stmt = $con->prepare($sql);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

                if($stmt->execute())
                {
                    $lead_data = $stmt->fetch();
                    $user_info->tot_leads = $lead_data['tot_leads'];
                    if(is_null($lead_data['tot_accepted_leads']))
                        $user_info->tot_accepted_leads = "0";
                    else
                        $user_info->tot_accepted_leads = $lead_data['tot_accepted_leads'];
                    
                    if(is_null($lead_data['tot_declined_leads']))
                        $user_info->tot_declined_leads = "0";
                    else
                        $user_info->tot_declined_leads = $lead_data['tot_declined_leads'];
                }
                
                if($user_info) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($user_info));
                } 
                else 
                { 
                    throw new PDOException('No records found');
                }
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
