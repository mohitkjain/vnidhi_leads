<?php

require_once 'settings/config.php';

$app->post('/api/test/active', function ($request, $response, $args) 
{
    require_once 'settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $userid = $parsedBody['userid'];

    if(isset($userid))
    {
        try
        {
            $con = connect_db();
            $stmt = '';
            $config = new config();
        
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
            }

            //print_r($users);

            echo $userids = implode(',', $users);


            // $select = "SELECT * FROM gallerier WHERE id IN ($in)";

            // If the API is JSON, use json_decode.
            //Get The Route you want... 
           // $path = $this->getContainer()->get('router')->pathFor('user_from_team', ['tl_id' => 4]);
             
            $stmt = $con->prepare("SELECT DISTINCT(lead_id), c_name, date_created, status FROM vn_leads WHERE (creator_id IN (:userid) OR assignee_id IN (:userid))");
            $stmt->bindParam(':userid', $userids);
    
            if ($stmt->execute()) 
            {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                header('Content-type: application/json');
                echo json_encode($data);
            }          
        }
        catch(PDOException $e)
        {
            echo '{"error" : {"text":'. $e->getMessage() .'}}';
            exit;
        }
       
    }
});