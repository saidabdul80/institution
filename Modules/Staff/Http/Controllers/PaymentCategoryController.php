<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Resources\APIResource;
use App\Models\PaymentCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Exception;

class PaymentCategoryController extends Controller
{
    /**
     * Get all payment categories with charges
     */
    public function index()
    {
        try {
            $categories = PaymentCategory::all();
            return new APIResource($categories, false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Update payment category charges
     */
    public function updateCharges(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:payment_categories,id',
                'charges' => 'required|numeric|min:0',
                'charge_type' => 'required|in:fixed,percentage',
                'charge_description' => 'nullable|string|max:500'
            ]);

            $category = PaymentCategory::findOrFail($request->id);
            
            // Validate percentage charges
            if ($request->charge_type === 'percentage' && $request->charges > 100) {
                throw new ValidationException(validator([], []), [
                    'charges' => ['Percentage charges cannot exceed 100%']
                ]);
            }

            $category->update([
                'charges' => $request->charges,
                'charge_type' => $request->charge_type,
                'charge_description' => $request->charge_description
            ]);

            return new APIResource([
                'message' => 'Payment category charges updated successfully',
                'category' => $category
            ], false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Calculate charges for a given amount and category
     */
    public function calculateCharges(Request $request)
    {
        try {
            $request->validate([
                'category_id' => 'required|exists:payment_categories,id',
                'amount' => 'required|numeric|min:0'
            ]);

            $category = PaymentCategory::findOrFail($request->category_id);
            $charges = $category->calculateCharges($request->amount);
            $totalAmount = $category->getTotalAmount($request->amount);

            return new APIResource([
                'amount' => $request->amount,
                'charges' => $charges,
                'total_amount' => $totalAmount,
                'charge_type' => $category->charge_type,
                'charge_description' => $category->charge_description
            ], false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Bulk update charges for multiple categories
     */
    public function bulkUpdateCharges(Request $request)
    {
        try {
            $request->validate([
                'categories' => 'required|array',
                'categories.*.id' => 'required|exists:payment_categories,id',
                'categories.*.charges' => 'required|numeric|min:0',
                'categories.*.charge_type' => 'required|in:fixed,percentage',
                'categories.*.charge_description' => 'nullable|string|max:500'
            ]);

            $updated = [];
            $errors = [];

            foreach ($request->categories as $categoryData) {
                try {
                    // Validate percentage charges
                    if ($categoryData['charge_type'] === 'percentage' && $categoryData['charges'] > 100) {
                        $errors[] = "Category ID {$categoryData['id']}: Percentage charges cannot exceed 100%";
                        continue;
                    }

                    $category = PaymentCategory::findOrFail($categoryData['id']);
                    $category->update([
                        'charges' => $categoryData['charges'],
                        'charge_type' => $categoryData['charge_type'],
                        'charge_description' => $categoryData['charge_description'] ?? null
                    ]);

                    $updated[] = $category;
                } catch (Exception $e) {
                    $errors[] = "Category ID {$categoryData['id']}: " . $e->getMessage();
                }
            }

            return new APIResource([
                'message' => count($updated) . ' categories updated successfully',
                'updated_categories' => $updated,
                'errors' => $errors,
                'success_count' => count($updated),
                'error_count' => count($errors)
            ], count($errors) > 0, count($errors) > 0 ? 400 : 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get payment category by short name
     */
    public function getByShortName(Request $request)
    {
        try {
            $request->validate([
                'short_name' => 'required|string'
            ]);

            $category = PaymentCategory::where('short_name', $request->short_name)->first();
            
            if (!$category) {
                return new APIResource('Payment category not found', true, 404);
            }

            return new APIResource($category, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }
}
