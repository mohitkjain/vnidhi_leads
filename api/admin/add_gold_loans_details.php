<?php

$app->post('/api/admin/gold_loans/add', function ($request, $response) 
{
    require_once '../api/settings/dbconnect.php';

    $parsedBody = $request->getParsedBody();
    $form_no = $parsedBody['form_no'];
    $customer_id = $parsedBody['customer_id'];
    $c_name = $parsedBody['c_name'];
    $c_address = $parsedBody['c_address'];
    $loan_date = $parsedBody['loan_date'];
    $loan_number = $parsedBody['loan_number'];
    $loan_scheme = $parsedBody['loan_scheme'];
    $loan_period = $parsedBody['loan_period'];
    $loan_amount = $parsedBody['loan_amount'];
    $loan_interest = $parsedBody['loan_interest'];
    $gold_description = $parsedBody['gold_description'];
    $c_image = $parsedBody['c_image'];
    $gold_image = $parsedBody['gold_image'];
    $no_of_items = $parsedBody['no_of_items'];
    $total_weight = $parsedBody['total_weight'];
    $processing_charges = $parsedBody['processing_charges'];
    $valuation_charges = $parsedBody['valuation_charges'];
    $peenal_interest = $parsedBody['peenal_interest'];
    $payment_mode = $parsedBody['payment_mode'];    

    $result = array();

    if(isset($form_no) && isset($customer_id) && isset($c_name) && isset($c_address) && isset($loan_date) && isset($loan_number) && isset($loan_scheme) && isset($loan_period) && isset($loan_amount) && isset($loan_interest) && isset($gold_description) && isset($c_image) && isset($gold_image) && isset($no_of_items) && isset($total_weight) && isset($processing_charges) && isset($valuation_charges) && isset($peenal_interest) && isset($payment_mode))
    {
        try
        {
            $con = connect_db();
            $sql = "INSERT INTO `vn_gold_loans_details`(`form_no`, `customer_id`, `c_name`, `c_address`, `loan_date`, `loan_number`, `loan_scheme`, `loan_period`, `loan_amount`, `loan_interest`, `gold_description`, `c_image`, `gold_image`, `no_of_items`, `total_weight`, `processing_charges`, `valuation_charges`, `peenal_interest`, `payment_mode`) VALUES (:form_no, :customer_id, :c_name, :c_address, :loan_date, :loan_number, :loan_scheme, :loan_period, :loan_amount, :loan_interest, :gold_description, :c_image, :gold_image, :no_of_items, :total_weight, :processing_charges, :valuation_charges, :peenal_interest, :payment_mode)";

            $stmt = $con->prepare($sql);
            $stmt->bindParam(':form_no', $form_no);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->bindParam(':c_name', $c_name);
            $stmt->bindParam(':c_address', $c_address);
            $stmt->bindParam(':loan_date', $loan_date);
            $stmt->bindParam(':loan_number', $loan_number);
            $stmt->bindParam(':loan_scheme', $loan_scheme);
            $stmt->bindParam(':loan_period', $loan_period);
            $stmt->bindParam(':loan_amount', $loan_amount);
            $stmt->bindParam(':loan_interest', $loan_interest);
            $stmt->bindParam(':gold_description', $gold_description);
            $stmt->bindParam(':c_image', $c_image);
            $stmt->bindParam(':gold_image', $gold_image);
            $stmt->bindParam(':no_of_items', $no_of_items);
            $stmt->bindParam(':total_weight', $total_weight);
            $stmt->bindParam(':processing_charges', $processing_charges);
            $stmt->bindParam(':valuation_charges', $valuation_charges);
            $stmt->bindParam(':peenal_interest', $peenal_interest);
            $stmt->bindParam(':payment_mode', $payment_mode);

            if($stmt->execute())
            {
                $id = $con->lastInsertId();
                if($id >= 1)
                {
                    $result['result'] = "success";
                }
                else
                {
                    $result['result'] = "failure";
                }                          
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