<?php

require_once 'settings/config.php';

$app->post('/api/leads/accepted', function ($request, $response) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $userid = $parsedBody['userid'];
    $usertype = $parsedBody['usertype'];

    if(isset($userid) && isset($usertype))
    {
        try
        {
            $con = connect_db();
            $stmt = '';
            $config = new config();

            //Prepare a Query Statement
            if($usertype === 'Telecaller')
            {
                $stmt = $con->prepare("SELECT lead_id, c_name, date_created, status, closing_date FROM vn_leads WHERE creator_id = :userid AND status IN ('accepted')");
                $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
            }
            else if($usertype === 'Teamleader')
            {
                $ch = curl_init();
                
                $url = $config->getteammates() ."". $userid;

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, 0);// No header in the result 
                
                // Fetch and return content, save it.
                $raw_data = curl_exec($ch);
                curl_close($ch);

                if(isset($raw_data))
                {
                    $raw_data = json_decode($raw_data);
                    foreach($raw_data as $row)
                    {
                        $users[] = $row->user_id;
                    }
                    array_push($users, $userid);

                    $in_params = array();
                    $in = "";
                    foreach ($users as $i => $item)
                    {
                        $key = ":id".$i;
                        $in .= "$key,";
                        $in_params[$key] = $item; // collecting values into key-value array
                    }
                    $userids = rtrim($in,","); 
    
                    $sql = "SELECT DISTINCT(lead_id), c_name, date_created, status, closing_date FROM vn_leads WHERE ((creator_id IN ($userids) OR assignee_id IN ($userids)) AND status IN ('accepted'))";

                    $stmt = $con->prepare($sql);
                    foreach ($users as $i => $item) 
                    {                 
                        $id = ":id".$i;
                        $stmt->bindParam($id, $in_params[$id]);
                    }
                }                
            }
            else if($usertype === 'Head')
            {
                $ch = curl_init();
                $url = $config->getleaders() ."". $userid; 
                
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, 0);// No header in the result
                
                // Fetch and return content, save it.
                $teamleaders = curl_exec($ch);
                curl_close($ch);
                $teamleaders = json_decode($teamleaders);
    
                foreach($teamleaders as $teamlead)
                {
                    $ch = curl_init();
                    $url = $config->getteammates() ."". $teamlead->user_id; 
                    
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_VERBOSE, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HEADER, 0);// No header in the result
                    
                    // Fetch and return content, save it.
                    $user_data = curl_exec($ch);
                    curl_close($ch);
                    $user_data = json_decode($user_data);
    
                    foreach($user_data as $user)
                    {
                        $users[] = $user->user_id;
                    }
                    array_push($users, $teamlead->user_id);
                }
                array_push($users, $userid);
               
                $in_params = array();
                $in = "";
                foreach ($users as $i => $item)
                {
                    $key = ":id".$i;
                    $in .= "$key,";
                    $in_params[$key] = $item; // collecting values into key-value array
                }
                $userids = rtrim($in,","); 
                
                $sql = "SELECT DISTINCT(lead_id), c_name, date_created, status, closing_date FROM vn_leads WHERE ((creator_id IN ($userids) OR assignee_id IN ($userids)) AND status IN ('accepted'))";
                
                $stmt = $con->prepare($sql);
                foreach ($users as $i => $item) 
                {                 
                    $id = ":id".$i;
                    $stmt->bindParam($id, $in_params[$id]);
                }
            }
            else if($usertype === 'Salaried')
            {
                $stmt = $con->prepare("SELECT lead_id, c_name, date_created, status, closing_date FROM vn_leads WHERE assignee_id = :userid AND status IN ('accepted')");
                $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
            }
            else
            {
                $stmt = $con->prepare("SELECT lead_id, c_name, date_created, status, closing_date FROM vn_leads WHERE status IN ('accepted')");
            }

            if ($stmt->execute()) 
            {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if($data) 
                {
                    return $response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($data));
                } 
                else 
                { 
                    throw new PDOException('No records found');
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
    }
});