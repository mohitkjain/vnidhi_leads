<?php

class Get_Gold_loan_Details
{
    public $id;
    public $form_no; 
    public $customer_id; 
    public $c_name; 
    public $c_address; 
    public $loan_date; 
    public $loan_number; 
    public $loan_scheme;
    public $loan_period; 
    public $loan_amount; 
    public $loan_interest; 
    public $gold_description; 
    public $c_image;
    public $gold_image; 
    public $no_of_items; 
    public $total_weight; 
    public $processing_charges; 
    public $valuation_charges;
    public $peenal_interest; 
    public $payment_mode; 
    public $created_date;
}

$app->get('/api/admin/gold_loans/{loan_id}', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';
    $loan_id = $request->getAttribute('loan_id');
    try
    {
        if(isset($loan_id))
        {
            $con = connect_db();
            //Prepare a Query Statement
            $sql = "SELECT * FROM `vn_gold_loans_details` WHERE id = :loan_id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':loan_id', $loan_id);
            if($stmt->execute())
            {
                $data = $stmt->fetchAll(PDO::FETCH_CLASS, "Get_Gold_loan_Details");            
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
            else 
            {
                throw new PDOException('No Record Found');
            }        
        }
        else
        {
            throw new PDOException('Please Provide all parameters.');
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