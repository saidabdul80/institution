<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\GenericExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class ExportController extends Controller
{
    public function exportData(Request $request)
    {
        
        // Validate request data
        $validator = Validator::make($request->all(), [
            'modelName' => 'required|string', // Ensure the model name is provided and is a string
            'ignoreColumns' => 'nullable|array',
            'headingName' => 'required|string',          
            'title' => 'nullable|string',
            'relationships' => 'nullable|array',
            'appends' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $relationships = $request->relationships ?? [];
        $appends = $request->appends ?? [];

        // Dynamically resolve model class from provided name
        $modelName = "App\\Models\\" . $request->modelName;
        if (!class_exists($modelName)) {
            return response()->json(['error' => 'Model not found.'], 404);
        }
        
        // Instantiate the model
        $model = new $modelName;

        // Prepare parameters
        $ignoreColumns = $request->ignoreColumns ?? [];
        $headingName = $request->headingName;
        //$logoPath = $request->logoPath ? public_path($request->logoPath) : null;
        $title = $request->title ?? null;

        // Create the export object
        $export = new GenericExport($model, $ignoreColumns, $headingName, tenant('logo')??'',$relationships,$appends);

        // Perform the export
        $fileName = $title ? $title . '.xlsx' : 'Export.xlsx';
        $response = Excel::download($export, $fileName, \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();
        return $response;
    }
}
