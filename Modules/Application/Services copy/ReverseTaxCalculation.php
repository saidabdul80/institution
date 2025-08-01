<?php

namespace App\Services;
use App\Models\CorporateTaxPayerEmployee;
use App\Models\TaxBracket;
use App\Traits\Tax;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReverseTaxCalculation
{
    use Tax;

    public static function generateSalariesForTaxAmount(float $totalTaxAmount, array $employees)
    {
        $generatedData = [];
        $remainingTaxAmount = $totalTaxAmount;

        foreach ($employees as $employee) {
            // Estimate salary that corresponds to average tax amount
            $estimatedSalary = self::estimateSalaryForTax($remainingTaxAmount / count($employees));

            // Recalculate tax for this employee based on the estimated salary
            $taxAmount = self::getTaxAmount($estimatedSalary);
            $name = Str::random(10);
            $generatedData[] = [
                'identifier' => $employee['identifier'],
                //remove @.... till the end 
                'full_name' => substr($employee['identifier'], 0, strpos($employee['identifier'], '@')),
                'designation' => self::getRandomDesignation(),
                'salary' => round($estimatedSalary, 2),
                'tax_amount' => round($taxAmount, 2),
            ];

            // Deduct the calculated tax from the total remaining tax amount
            $remainingTaxAmount -= $taxAmount;
        }

        // if (abs($remainingTaxAmount) > 0.01) {
        //     $lastEmployee = &$generatedData[count($generatedData) - 1];
        //     $lastSalary = $lastEmployee['salary'];
        
        //     // Adjust last employee's salary to balance the tax amount
        //     $adjustedSalary = self::adjustSalaryForTax($lastSalary, $remainingTaxAmount);
        //     $lastEmployee['salary'] = round($adjustedSalary, 2);
        //     $lastEmployee['tax_amount'] = round(self::getTaxAmount($adjustedSalary), 2);
        
        //     // Recalculate remaining tax amount to ensure no discrepancy
        //     $remainingTaxAmount -= $lastEmployee['tax_amount'] - self::getTaxAmount($lastSalary);
        // }

        $totalGeneratedTax = array_sum(array_column($generatedData, 'tax_amount'));
        $difference = $totalTaxAmount - $totalGeneratedTax;
    
        if (abs($difference) > 0.01) {
            $lastEmployee = &$generatedData[count($generatedData) - 1];
            $lastSalary = $lastEmployee['salary'];

            // Adjust the last employee's salary to balance the tax amount difference
            $adjustedSalary = self::adjustSalaryForTax($lastSalary, $difference + self::getTaxAmount($lastSalary));
            $lastEmployee['salary'] = round($adjustedSalary, 2);
            $lastEmployee['tax_amount'] = round(self::getTaxAmount($adjustedSalary), 2);
        }

       // Log::info($generatedData);
        // // Adjust the last employee's tax to ensure the total matches exactly
        // if (abs($remainingTaxAmount) > 0.01) {
        //     $lastEmployee = &$generatedData[count($generatedData) - 1];
        //     $adjustedSalary = self::adjustSalaryForTax($lastEmployee['salary'], $remainingTaxAmount);
        //     $lastEmployee['salary'] = round($adjustedSalary, 2);
        //     $lastEmployee['tax_amount'] = round(self::getTaxAmount($adjustedSalary), 2);
        // }

        return $generatedData;
    }

    public static function estimateSalaryForTax($desiredTaxAmount)
    {
        // Define tax brackets
        $taxBrackets = [
            ['amount' => 300000, 'percentage' => 7],
            ['amount' => 300000, 'percentage' => 11],
            ['amount' => 500000, 'percentage' => 15],
            ['amount' => 500000, 'percentage' => 19],
            ['amount' => 1600000, 'percentage' => 21],
            ['amount' => 3200000, 'percentage' => 24],
            //['amount' => PHP_INT_MAX, 'percentage' => 24], // "Above" bracket
        ];

        $salary = 0;
        $accumulatedTax = 0;

        

        foreach ($taxBrackets as $bracket) {
            $bracketLimit = $bracket['amount'];
            $taxRate = $bracket['percentage'] / 100;

            $taxableAmount = $desiredTaxAmount - $accumulatedTax;
            if ($taxableAmount <= $bracketLimit * $taxRate) {
                // Remaining tax fits in this bracket
                $salary += $taxableAmount / $taxRate;
                return $salary;
            }

            // Add the full salary for this bracket
            $salary += $bracketLimit;
            $accumulatedTax += $bracketLimit * $taxRate;
        }

        return $salary;
    }

    
    // private static function estimateSalaryForTax($desiredTaxAmount)
    // {
    //     $low = 0;
    //     $high = 1_000_000; // Assume a reasonable maximum salary

    //     while ($high - $low > 0.01) {
    //         $mid = ($low + $high) / 2;
    //         $calculatedTax = self::getTaxAmount($mid);

    //         if ($calculatedTax < $desiredTaxAmount) {
    //             $low = $mid;
    //         } else {
    //             $high = $mid;
    //         }
    //     }

    //     return ($low + $high) / 2;
    // }

    private static function adjustSalaryForTax($currentSalary, $taxAdjustment)
    {
      
         // Dynamically determine the number of zeros based on the length of the tax adjustment
            $zeros = str_repeat('0', strlen((string) $taxAdjustment));
            
            $low = $currentSalary - 10_000;
            $high = $currentSalary + (10_000 * (int) '1'.$zeros);
            $precision = 0.01; // Precision for tax adjustment

    
        while ($high - $low > $precision) {

            $mid = ($low + $high) / 2;
            $calculatedTax = self::getTaxAmount($mid);
    
          
            // if($calculatedTax == $taxAdjustment){
            //     return $calculatedTax;
            // }
            
            if ($calculatedTax < $taxAdjustment) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }
    
        // Return the midpoint if the exact match isn't found
        return ($low + $high) / 2;
    }
    
    private static function getRandomDesignation()
    {
        $designations = ["Manager", "Engineer", "Clerk", "Accountant", "Technician"];
        return $designations[array_rand($designations)];
    }
}
