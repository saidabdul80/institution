<?php

function generateInvoiceNumber(){
    return date("ym").rand(100, 999).date('is');
}

function generateWalletNumber(){
    return date("ym").rand(1000, 9999).date('i');
}

function generateTrxId(){
    $number = "ttid" . date("ymdhis") . rand(100, 999);
    return $number;
}

function formatError($errors){
    return collect($errors)->first();
}

/**
 * Validate an array of data against a set of data type rules.
 *
 * @param array $data The data to be validated.
 * @param array $rule An associative array of allowed keys and their corresponding data types.
 * @throws \Exception if a data type does not match the corresponding rule.
 * @return bool true if all data types match their corresponding rules.
 */
function validateData(array $data, array $rule) {        
    foreach ($data as $key => $value) {             
        if (gettype($value) !== $rule[$key]) {            
            throw new \Exception("Invalid data type for key '{$key}'");            
        } 
    }    
    return true;
}

