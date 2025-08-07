<?php
namespace App\Services;

use App\Models\Configuration;
use App\Models\Student;
use App\Models\StudentNew;
use App\Models\Wallet;
use App\Repositories\ConfigurationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Modules\Staff\Repositories\ProgrammeRepository;

class Utilities {

    private $configurationRepository;
    public function __construct(ConfigurationRepository $configurationRepository)
    {
        $this->configurationRepository = $configurationRepository;
    }

    public function fileToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return response()->json(['error' => "Error while reading file"], 400);

        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$header){
                    $header =  $row;
                    foreach($header as &$hd){
                        $hd = strtolower($hd);
                    }
                }
                else{
                    if(sizeof($header) != sizeof($row)){
                        if(sizeof($header) > sizeof($row)){
                           $row[] = '';
                        }else{
                            unset($row[sizeof($row)-1]);
                        }
                    }
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * @var file name in public/template folder
     */
    public function getFile($filename){
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );
        return response()->download(public_path('templates/'.$filename), $filename, $headers);
    }

    static private function getSessionYear($session_id){
        $current_application_session = DB::table('sessions')->where('id',$session_id)->first();
        if(empty($current_application_session)){
            throw new \Exception('current application session not found');
        }
        $session_split = explode('/',$current_application_session->name);
       return substr($session_split[0],2,2);
    }

    static public function number_formater($format, $user, $num, $configurationRepository){
        if(!is_array($user)){
            $user = $user->toArray();
        }

        if($user['session_id']??'' === ''){
            //only applicant does not comes with session id
            $current_application_session = $configurationRepository::fetchValue('current_application_session');
            $year  = self::getSessionYear($current_application_session);
        }else{
            $year  = self::getSessionYear($user['session_id']);
        }
        
        $departmentEnabled = false;
        $entryModeEnabled = false;
        $programmeTypeEnabled = false;
        // Perform the configuration check
        if ($configurationRepository::check('enable_department', 'true')) {
            $departmentEnabled = true;
        }

        if ($configurationRepository::check('enable_entry_mode', 'true')) {
            $entryModeEnabled = true;
        }

        if ($configurationRepository::check('enable_programme_type', 'true')) {
            $programmeTypeEnabled = true;
        }

        if($departmentEnabled){
            $department = DB::table('departments','d')->selectRaw('faculties.abbr as fabbr, d.*')->join('faculties','faculties.id','d.faculty_id')->where('d.id', $user['department_id'])->first();
        }
        if($entryModeEnabled){
            $entry_mode = DB::table('entry_modes')->where('id', $user['mode_of_entry_id'])->first()?->code;
        }

        $level = DB::table('levels')->where('id',$user['level_id'] ?? $user['applied_level_id'] ?? 1)->first()?->order;
        if($programmeTypeEnabled){
            $programme_type = DB::table('programme_types')->find($user['programme_type_id'])?->short_name;
        }
        // if(array_key_exists('applied_programme_curriculum_id', $user)){
        //     $user['programme_id'] = $user['applied_programme_curriculum_id'];
      
        $programme_id = $user['programme_id'] ?? $user['applied_programme_curriculum_id'];  
        $programme = DB::table('programmes')->where('id',$programme_id)->first()?->code;        
        if($programme == ''){
            throw new \Exception('No Program found');
        }
        
        try{

            $school = Http::withHeaders([
                'Accept' => 'application/json',
                'xtenant' => request()->getHost()
                ])->get(env('BASE_URL').'/api/school-info');
        }catch(\Exception $e){

        }


        $acronym = $school['acronym']??"App";

        $format_data = [
            "{school_acronym}" => $acronym,
        ];
        
        if ($departmentEnabled) {
            $format_data["{faculty}"] = $department ? $department->fabbr : null;
            $format_data["{department}"] = $department ? $department->abbr : null;
        }
        
        if ($entryModeEnabled) {
            $format_data["{entry_mode}"] = $entry_mode;
        }
        
        if ($programmeTypeEnabled) {
            $format_data["{programme_type}"] = $programme_type;
        }
        
        $format_data["{programme_code}"] = $programme;
        $format_data["{session}"] = $year;
        $format_data["{level}"] = $level;
        
        $format_data["{number}"] = $num;        
        
        
        foreach($format_data as $key=>$value){
            $format =  str_replace($key,$value,$format);
        }
        return $format;
    }


    public function getTemplateFormatSeparator($str){
        return $str[strpos($str,'}')+1];
    }

    static public function getTemplateFormatSeparatorStatic($str){
        return $str[strpos($str,'}')+1];
    }

    public function getNextNumberFromString($tablename, $column_name, $user){
        $programmeTypeEnabled = $this->configurationRepository->exists('enable_programme_type','true');
        if($programmeTypeEnabled){
            $programme_type_id = $user['programme_type_id']??'';
            if($programme_type_id??'' === ''){
                $programme_type_id = DB::table('programmes')->find($user['programme_id'])?->programme_type_id;
            }
            $number_format_template = DB::table('configurations')->where(['name'=>$column_name.'_format', 'programme_type_id'=>$programme_type_id])->first();
        }else{
            $number_format_template = $this->configurationRepository->getValue($column_name.'_format');
        }
        
        if(empty($number_format_template)){
            throw new \Exception("Invalid configuration on ". $column_name.'_format');
        }
        $number_format_template = $number_format_template;
        $number_format_example = $this->number_formater($number_format_template,$user, '', $this->configurationRepository);
        
        //$separator = $this->getTemplateFormatSeparatorStatic($number_format_template);
        if($programmeTypeEnabled){
            $matric_number_numbering_format = DB::table('configurations')->where(['name'=>$column_name.'_numbering_format', 'programme_type_id'=>$user['programme_type_id']])->first();
        }else{
            $matric_number_numbering_format = $this->configurationRepository->getValue($column_name.'_numbering_format');
        }
        
        if(empty($matric_number_numbering_format)){
            throw new \Exception("Invalid configuration on ". $column_name.'_numbering_format');
        }
        $matric_number_numbering_format = $matric_number_numbering_format;
        
        if($tablename == 'students'){
            if($matric_number_numbering_format =='zero_prefix'){


                $num =  DB::table($tablename,'tablename')->selectRaw("MAX( CONVERT(REPLACE(tablename.$column_name,'$number_format_example',''), UNSIGNED)) as lastNumber")->whereRaw("$column_name Like '$number_format_example%'")->first();
                if(is_null($num)){

                    $num = 1;
                    return str_pad($num,4,'0',STR_PAD_LEFT);
                }else{
                    $number = (int) $num->lastNumber +1;
                    return str_pad($number,4,'0',STR_PAD_LEFT);;
                }
            }else if ($matric_number_numbering_format == 'level_prefix'){
                $level_id = $user['level_id'];


                $level = DB::table('levels')->where('id',$level_id)->first();                
                $num =  DB::table($tablename,'tablename')->selectRaw("MAX( CONVERT(REPLACE(tablename.$column_name,'$number_format_example',''), UNSIGNED)) as lastNumber")->whereRaw("$column_name Like '$number_format_example%'")->where('level_id', $level_id)->first();                                
                
                if(is_null($num?->lastNumber)){                                
                    $num = $level->order.'001';
                    return str_pad($num,4,'0',STR_PAD_LEFT);
                }else{
                    $number = (int) $num->lastNumber +1;
                    return $number;
                }
            }else if ($matric_number_numbering_format == 'session_prefix'){                
                $year = self::getSessionYear($user['session_id']);                      
                $num =  DB::table($tablename,'tablename')->selectRaw("MAX( CONVERT(REPLACE(tablename.$column_name,'$number_format_example',''), UNSIGNED)) as lastNumber")->whereRaw("$column_name Like '$number_format_example%'")->first();                                                            
                if (is_null($num?->lastNumber)) {
                    return $number_format_example. $year . '001';
                } else {
                    return $number_format_example. ((int) $num->lastNumber + 1);                  
                }              
            }else{
                throw new \Exception('Invalid numbering fortter on configuration');
            }
        }else{

            $num =  DB::table($tablename,'tablename')->selectRaw("MAX( CONVERT(REPLACE(tablename.$column_name,'$number_format_example',''), UNSIGNED)) as lastNumber")->whereRaw("$column_name Like '$number_format_example%'")->first();
            
            if(is_null($num)){
                $num = 1;
                return $number_format_example. str_pad($num,4,'0',STR_PAD_LEFT);
            }else{
                $number = (int) $num->lastNumber +1;
                return $number_format_example. str_pad($number,4,'0',STR_PAD_LEFT);
            }
        }


    }

    static public function getNextNumberFromStringStatic($tablename, $column_name, $user){
        $number_format_template = DB::table('configurations')->where(['name'=>$column_name.'_format'])->where(function($query) use($user){
            $query->where('programme_type_id', $user['programme_type_id'])->orWhereNull('programme_type_id');
        })->first();
        if(empty($number_format_template)){
            throw new \Exception("Invalid configuration on ". $column_name.'_format');
        }
        $number_format_template = $number_format_template->value;
        $number_format_example = self::number_formater($number_format_template,$user, '');
        $separator = self::getTemplateFormatSeparatorStatic($number_format_template);
        $matric_number_numbering_format = DB::table('configurations')->where(['name'=>$column_name.'_numbering_format', 'programme_type_id'=>$user['programme_type_id']])->first();
        if(empty($matric_number_numbering_format)){
            throw new \Exception("Invalid configuration on ". $column_name.'_numbering_format');
        }
        $matric_number_numbering_format = $matric_number_numbering_format->value;
        if($matric_number_numbering_format =='zero_prefix'){


            $num =  DB::table($tablename,'tablename')->selectRaw("MAX( CONVERT(REPLACE(tablename.$column_name,'$number_format_example',''), UNSIGNED)) as lastNumber")->whereRaw("$column_name Like '$number_format_example%'")->first();                           
            if(is_null($num?->lastNumber)){                

                $num = 1;
                return str_pad($num,4,'0',STR_PAD_LEFT);
            }else{
                $number = (int) $num->lastNumber +1;
                return str_pad($number,4,'0',STR_PAD_LEFT);;
            }
        }else if ($matric_number_numbering_format == 'level_prefix'){

            $level_id = $user['level_id'];

            $level = DB::table('levels')->where('id',$level_id)->first();


            $num =  DB::table($tablename,'tablename')->selectRaw("MAX( CONVERT(REPLACE(tablename.$column_name,'$number_format_example',''), UNSIGNED)) as lastNumber")->whereRaw("$column_name Like '$number_format_example%'")->where('level_id', $level_id)->first();                
            if(is_null($num?->lastNumber)){                                                

                $num = $level->order.'001';
                return $num;
            }else{
                $number = (int) $num->lastNumber +1;
                return $number;
            }
        }else if ($matric_number_numbering_format == 'session_prefix'){                            
            $year = self::getSessionYear($user['session_id']);                      
            $num =  DB::table($tablename,'tablename')->selectRaw("MAX( CONVERT(REPLACE(tablename.$column_name,'$number_format_example',''), UNSIGNED)) as lastNumber")->whereRaw("$column_name Like '$number_format_example%'")->first();                                                            
            if (is_null($num?->lastNumber)) {
                return $year . '001';
            } else {
              return (int) $num->lastNumber + 1;                  
            }                
        }else{
            throw new \Exception('Invalid numbering formatter on configuration');
        }
    }

    public static function makeNewStudent($applicant_info){
        $trials = 0;

        $matric_number_format = DB::table('configurations')->where(['name'=>'matric_number_format','programme_type_id'=>$applicant_info->programme_type_id])->first();
        if(empty($matric_number_format)){
            throw new \Exception("Invalid configuration on matric_number_format");
        }
        $matric_number_format = $matric_number_format->value;
        if(empty($matric_number_format)){
            throw new \Exception("Invalid Matric Number Format");
        }
        $applicant_programme = ProgrammeRepository::getProgrammeById($applicant_info->programme_id);

        $num = self::getNextNumberFromStringStatic('students','matric_number', $applicant_info);
        if(!empty($applicant_programme)){
            $applicantObject = DB::table('applicants')->where('id', $applicant_info->id)->first();
            $applicant = json_decode(json_encode($applicantObject), true);
            $applicant['department_id'] = $applicant_programme->department_id;
            $applicant['programme_id'] = $applicant_info->programme_id;
            $applicant['entry_level_id'] = $applicant_info->level_id;
            $new_matric_number = self::number_formater($matric_number_format,$applicant,$num);

            $student_columns = DB::getSchemaBuilder()->getColumnListing('students');
            unset($student_columns[0]); //unset id from the array
            $applicant['entry_session_id'] = $applicant['session_id'];
            $applicant['application_id'] = $applicant['id'];
            $applicant['matric_number'] = $new_matric_number;
            foreach($applicant as $column => $value){
                if(!in_array($column,$student_columns)){
                    unset($applicant[$column]);
                }
            }

            if(DB::table('students')->where('matric_number', $new_matric_number)->exists()){
                Log::error("Something went wrong generating matric number for applicant: ".$applicant_info->application_number." after 3 trials with Matric No. ".$new_matric_number);
                return;
            }

            try{
                DB::transaction(function() use($applicant){
                    $student_id = DB::table('students')->insertGetId($applicant);
                    Wallet::where('wallet_number',$applicant['wallet_number'])->update(['owner_type'=>'App\\Models\\Student','owner_id'=>$student_id]);
                    DB::table('student_enrollments')->insert([
                            "owner_id"=> $applicant['application_id'],
                            "owner_type"=> 'applicant',
                            "session_id"=>$applicant['entry_session_id'],
                            "token"=> 'applicant'.$applicant['application_id'].$applicant['entry_session_id']
                    ]);

                });
            }catch(\Exception $e){
                Log::error($e->getMessage());
                throw new \Exception('Something Went Wrong Migrating Applicant', 400);
            }
        }
    }

    static public function currentSession()
    {
        return DB::table('configurations')->where('name', 'current_session')->first()->value;
    }

    public function graduationLevel()
    {
        return DB::table('configurations')->where('name', 'grad_level_id')->first()->value;
    }

    public function removeAllAccessors($table_name,$data){
        $columns = DB::getSchemaBuilder()->getColumnListing($table_name);
        foreach($data as $column => $value){
            if(!in_array($column, $columns)){
                unset($data[$column]);
            }else{
                if($value == "" ){
                    $data[$column] = NULL;
                }
            }
        }
        return $data;
    }

}




