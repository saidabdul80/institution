<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Resources\APIResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use App\Models\Applicant;
use App\Models\Programme;
use App\Models\EntryMode;
use App\Models\LGA;
use App\Models\Session;
use Illuminate\Support\Facades\Log;

class ApplicantImportController extends Controller
{
    /**
     * Upload and validate Excel file for applicant import
     */
    public function uploadFile(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
                'session_id' => 'required|integer'
            ]);

            $file = $request->file('file');
            $sessionId = $request->get('session_id');

            // Generate unique batch ID
            $batchId = 'IMPORT_' . date('YmdHis') . '_' . Str::random(6);

            // Read Excel file
            $data = Excel::toArray([], $file);

            if (empty($data) || empty($data[0])) {
                return new APIResource('Excel file is empty or invalid', true, 400);
            }

            $rows = $data[0];
            $headers = array_shift($rows); // Remove header row

            // Validate headers
            $requiredHeaders = ['full_name', 'jamb_number', 'gender', 'programme_name', 'mode_of_entry', 'lga_name'];
            $optionalHeaders = ['subject_1', 'score_1', 'subject_2', 'score_2', 'subject_3', 'score_3', 'subject_4', 'score_4'];

            $missingHeaders = array_diff($requiredHeaders, $headers);
            if (!empty($missingHeaders)) {
                return new APIResource('Missing required headers: ' . implode(', ', $missingHeaders), true, 400);
            }

            // Validate and prepare data
            $validatedData = [];
            $errors = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and Excel starts from 1
                $rowData = array_combine($headers, $row);

                $validation = $this->validateRow($rowData, $rowNumber, $sessionId, $batchId);

                if ($validation['valid']) {
                    $validatedData[] = $validation['data'];
                } else {
                    $errors = array_merge($errors, $validation['errors']);
                }
            }

            if (!empty($errors)) {
                return new APIResource([
                    'message' => 'Validation errors found',
                    'errors' => $errors,
                    'valid_count' => count($validatedData),
                    'error_count' => count($errors)
                ], true, 400);
            }

            // Store validated data temporarily (you might want to use cache or temporary table)
            cache()->put("import_data_{$batchId}", $validatedData, now()->addHours(1));

            return new APIResource([
                'message' => 'File validated successfully',
                'batch_id' => $batchId,
                'total_records' => count($validatedData),
                'preview' => array_slice($validatedData, 0, 5) // First 5 records for preview
            ], false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            Log::error($e);
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Validate individual row data
     */
    private function validateRow($rowData, $rowNumber, $sessionId, $batchId = null)
    {
        $errors = [];
        $data = [];

        // Validate full name and split it
        if (empty($rowData['full_name'])) {
            $errors[] = "Row {$rowNumber}: Full name is required";
        } else {
            $nameParts = $this->splitFullName($rowData['full_name']);
            $data['first_name'] = $nameParts['first_name'];
            $data['middle_name'] = $nameParts['middle_name'];
            $data['surname'] = $nameParts['surname'];
        }

        // Validate JAMB number
        if (empty($rowData['jamb_number'])) {
            $errors[] = "Row {$rowNumber}: JAMB number is required";
        } else {
            // Check if JAMB number already exists
            $existingApplicant = Applicant::where('jamb_number', $rowData['jamb_number'])->first();
            if ($existingApplicant) {
                $errors[] = "Row {$rowNumber}: JAMB number {$rowData['jamb_number']} already exists";
            } else {
                $data['jamb_number'] = $rowData['jamb_number'];
            }
        }

        // Validate gender
        if (empty($rowData['gender'])) {
            $errors[] = "Row {$rowNumber}: Gender is required";
        } else {
            $gender = strtolower($rowData['gender']);
            if (!in_array($gender, ['male', 'female', 'other'])) {
                $errors[] = "Row {$rowNumber}: Invalid gender. Must be male, female, or other";
            } else {
                $data['gender'] = $gender;
            }
        }

        // Validate programme name and get programme ID
        if (empty($rowData['programme_name'])) {
            $errors[] = "Row {$rowNumber}: Programme name is required";
        } else {
            $programme = Programme::where('name', 'LIKE', '%' . $rowData['programme_name'] . '%')->first();
            if (!$programme) {
                $errors[] = "Row {$rowNumber}: Programme '{$rowData['programme_name']}' not found";
            } else {
                $data['applied_programme_id'] = $programme->id;
                $data['programme_id'] = $programme->id;
                $data['department_id'] = $programme->department_id;
                $data['faculty_id'] = $programme->faculty_id;
                $data['programme_type_id'] = $programme->programme_type_id;
            }
        }

        // Validate mode of entry and get entry mode ID
        if (empty($rowData['mode_of_entry'])) {
            $errors[] = "Row {$rowNumber}: Mode of entry is required";
        } else {
            $entryMode = EntryMode::where('title', 'LIKE', '%' . $rowData['mode_of_entry'] . '%')->first();
            if (!$entryMode) {
                $errors[] = "Row {$rowNumber}: Mode of entry '{$rowData['mode_of_entry']}' not found";
            } else {
                $data['mode_of_entry_id'] = $entryMode->id;
            }
        }

        // Validate LGA name and get LGA ID
        if (empty($rowData['lga_name'])) {
            $errors[] = "Row {$rowNumber}: LGA name is required";
        } else {
            $lga = LGA::where('name', 'LIKE', '%' . $rowData['lga_name'] . '%')->first();
            if (!$lga) {
                $errors[] = "Row {$rowNumber}: LGA '{$rowData['lga_name']}' not found";
            } else {
                $data['lga_id'] = $lga->id;
                $data['state_id'] = $lga->state_id;
                $data['country_id'] = $lga->state->country_id ?? 1; // Default to Nigeria
            }
        }

        // Process JAMB subject scores
        $jambScores = [];
        for ($i = 1; $i <= 4; $i++) {
            $subjectKey = "subject_{$i}";
            $scoreKey = "score_{$i}";

            if (!empty($rowData[$subjectKey]) && !empty($rowData[$scoreKey])) {
                $score = (int) $rowData[$scoreKey];
                if ($score < 0 || $score > 100) {
                    $errors[] = "Row {$rowNumber}: Invalid score for {$rowData[$subjectKey]}. Must be between 0-100";
                } else {
                    $jambScores[$rowData[$subjectKey]] = $score;
                }
            }
        }

        if (!empty($jambScores)) {
            $data['jamb_subject_scores'] = json_encode($jambScores); // Let the model cast handle JSON encoding
        }

        // Add default values
        $data['session_id'] = intval($sessionId);
        $data['password'] = Hash::make($rowData['jamb_number']); // Default password is JAMB number
        $data['application_number'] = $this->generateApplicationNumber($sessionId, $batchId);
        $data['is_imported'] = true;
        $data['imported_at'] = now();
        $data['application_fee_paid'] = false;
        $data['import_batch_id'] = $batchId;

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $data
        ];
    }

    /**
     * Split full name into first, middle, and last name
     */
    private function splitFullName($fullName)
    {
        $nameParts = explode(' ', trim($fullName));
        $firstName = $nameParts[0] ?? '';
        $surname = end($nameParts);

        // If there are more than 2 parts, everything in between is middle name
        $middleName = '';
        if (count($nameParts) > 2) {
            $middleParts = array_slice($nameParts, 1, -1);
            $middleName = implode(' ', $middleParts);
        }

        // If only 2 parts, no middle name
        if (count($nameParts) == 2) {
            $middleName = null;
        }

        return [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'surname' => $surname
        ];
    }

    /**
     * Generate application number
     */
    private function generateApplicationNumber($sessionId, $batchId = null)
    {
        $session = DB::table('sessions')->find($sessionId);
        $year = $session ? date('Y', strtotime($session->name)) : date('Y');
        $prefix = "UTME/{$year}/IMP";

        // Use database transaction for atomic increment
        return DB::transaction(function () use ($sessionId, $prefix) {
            $tracker = DB::table('application_number_tracker')
                ->where('session_id', $sessionId)
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if ($tracker) {
                $nextNumber = $tracker->last_number + 1;
                DB::table('application_number_tracker')
                    ->where('id', $tracker->id)
                    ->update(['last_number' => $nextNumber]);
            } else {
                $nextNumber = 1;
                DB::table('application_number_tracker')->insert([
                    'session_id' => $sessionId,
                    'prefix' => $prefix,
                    'last_number' => $nextNumber,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            $applicationNumber = sprintf("%s/%04d", $prefix, $nextNumber);

            // Double-check that the generated number doesn't exist
            $exists = DB::table('applicants')
                ->where('application_number', $applicationNumber)
                ->exists();

            if ($exists) {
                // If it exists, increment the tracker and generate a new one
                DB::table('application_number_tracker')
                    ->where('session_id', $sessionId)
                    ->where('prefix', $prefix)
                    ->increment('last_number');

                $nextNumber = $nextNumber + 1;
                $applicationNumber = sprintf("%s/%04d", $prefix, $nextNumber);
            }

            return $applicationNumber;
        });
    }

    /**
     * Process the import after validation
     */
    public function processImport(Request $request)
    {
        try {
            $request->validate([
                'batch_id' => 'required|string',
                'session_id' => 'required|integer'
            ]);

            $batchId = $request->get('batch_id');
            $sessionId = intval($request->get('session_id'));

            // Verify session exists
            $session = \App\Models\Session::find($sessionId);
            if (!$session) {
                return new APIResource('Invalid session ID. Please select a valid session.', true, 400);
            }

            // Retrieve validated data from cache
            $validatedData = cache()->get("import_data_{$batchId}");

            if (!$validatedData) {
                return new APIResource('Import data not found or expired. Please upload the file again.', true, 400);
            }

            DB::beginTransaction();

            $imported = [];
            $failed = [];

            foreach ($validatedData as $data) {
                try {
                    // Ensure session_id is set correctly
                    $data['session_id'] = $sessionId;
                    $data['import_batch_id'] = $batchId;

                    // Validate required fields including foreign keys
                    $requiredFields = [
                        'first_name', 'surname', 'jamb_number', 'session_id',
                        'applied_programme_id', 'programme_id', 'mode_of_entry_id'
                    ];
                    
                    foreach ($requiredFields as $field) {
                        if (empty($data[$field])) {
                            throw new Exception("Required field '{$field}' is missing or empty");
                        }
                    }

                    // Check for duplicate application number before inserting
                    $existingApp = DB::table('applicants')
                        ->where('application_number', $data['application_number'])
                        ->first();

                    if ($existingApp) {
                        // Generate a new application number
                        $data['application_number'] = $this->generateApplicationNumber($sessionId, $batchId);

                        // Double-check the new number
                        $stillExists = DB::table('applicants')
                            ->where('application_number', $data['application_number'])
                            ->exists();

                        if ($stillExists) {
                            throw new Exception("Unable to generate unique application number after retry");
                        }
                    }

                    // Use DB::table to bypass the model's newQuery override
                    DB::table('applicants')->insert(array_merge($data, [
                        'created_at' => now(),
                        'updated_at' => now()
                    ]));

                    // Get the created applicant for the response
                    $applicant = DB::table('applicants')
                        ->where('jamb_number', $data['jamb_number'])
                        ->where('import_batch_id', $batchId)
                        ->first();
                    $imported[] = $applicant;
                } catch (Exception $e) {
                    Log::error('Applicant import failed', [
                        'data' => $data,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $failed[] = [
                        'data' => $data,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            // Clear cache
            cache()->forget("import_data_{$batchId}");

            return new APIResource([
                'message' => 'Import completed successfully',
                'imported_count' => count($imported),
                'failed_count' => count($failed),
                'batch_id' => $batchId,
                'failed_records' => $failed
            ],  count($failed)>0,  count($failed)>0?400:200);

        } catch (ValidationException $e) {
            DB::rollBack();
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            DB::rollBack();
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    /**
     * Get import history
     */
    public function getImportHistory(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|integer',
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100'
            ]);

            $sessionId = $request->get('session_id');
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            $imports = DB::table('applicants')
                ->select('import_batch_id',
                    DB::raw('COUNT(*) as total_imported'),
                    DB::raw('MIN(imported_at) as imported_at'),
                    DB::raw('COUNT(CASE WHEN application_fee_paid = 1 THEN 1 END) as paid_count'))
                ->where('session_id', $sessionId)
                ->where('is_imported', true)
                ->whereNotNull('import_batch_id')
                ->groupBy('import_batch_id')
                ->orderBy('imported_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return new APIResource($imports, false, 200);

        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        try {
            $headers = [
                'full_name',
                'jamb_number',
                'gender',
                'programme_name',
                'mode_of_entry',
                'lga_name',
                'subject_1',
                'score_1',
                'subject_2',
                'score_2',
                'subject_3',
                'score_3',
                'subject_4',
                'score_4'
            ];

            $sampleData = [
                [
                    'John Doe Smith',
                    '12345678AB',
                    'male',
                    'Computer Science',
                    'UTME',
                    'Ikeja',
                    'Mathematics',
                    '85',
                    'Physics',
                    '78',
                    'Chemistry',
                    '82',
                    'English Language',
                    '75'
                ],
                [
                    'Jane Mary Johnson',
                    '87654321CD',
                    'female',
                    'Business Administration',
                    'DE',
                    'Lagos Island',
                    'Economics',
                    '90',
                    'Government',
                    '88',
                    'Literature',
                    '85',
                    'English Language',
                    '92'
                ]
            ];

            $data = array_merge([$headers], $sampleData);

            // Create a proper Excel export class
            $export = new class($data) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles {
                private $data;

                public function __construct($data) {
                    $this->data = $data;
                }

                public function array(): array {
                    // Return data without headers since we're using WithHeadings
                    return array_slice($this->data, 1);
                }

                public function headings(): array {
                    return $this->data[0];
                }

                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                    return [
                        // Style the first row as header
                        1 => [
                            'font' => ['bold' => true, 'size' => 12],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFE2E8F0']
                            ]
                        ],
                    ];
                }
            };

            return Excel::download($export, 'applicant_import_template.xlsx', \Maatwebsite\Excel\Excel::XLSX, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }

    /**
     * Get available sessions for import
     */
    public function getSessions()
    {
        try {
            $sessions = Session::orderBy('name', 'desc')->get(['id', 'name']);
            return new APIResource($sessions, false, 200);
        } catch (Exception $e) {
            return new APIResource($e->getMessage(), true, 500);
        }
    }
}
