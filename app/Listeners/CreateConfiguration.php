<?php

namespace App\Listeners;

use App\Events\ProgrammeTypeCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class CreateConfiguration
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ProgrammeTypeCreated $event)
    {
        $programmeType = $event->programmeType;
        $configurations = array(
            array('name' => 'allow_course_registration','value' => '','model' => 'App\\Models\\Level','field_type' => 'checkbox','seeds' => '','programme_type_id' => $programmeType->id,'updated_by' => NULL,'created_at' => NULL,'updated_at' => NULL),
            array('name' => 'allow_payments','value' => '','model' => 'App\\Models\\Level','field_type' => 'checkbox','seeds' => '','programme_type_id' => $programmeType->id,'updated_by' => NULL,'created_at' => NULL,'updated_at' => NULL),
            array('name' => 'application_number_format','value' => 'EUM/{entry_mode}/{session}/{number}','model' => '','field_type' => 'select','seeds' => '{school_acronym},{faculty},{department},{entry_mode},{programme_code},{programme_type},{session},{number},{level}','programme_type_id' => $programmeType->id,'updated_by' => NULL,'created_at' => NULL,'updated_at' => NULL),
            array('name' => 'application_number_numbering_format','value' => 'zero_prefix','model' => '','field_type' => 'select','seeds' => 'zero_prefix,level_prefix','programme_type_id' => $programmeType->id,'updated_by' => NULL,'created_at' => NULL,'updated_at' => NULL),
            array('name' => 'matric_number_format','value' => '{faculty}/{programme_code}/{session}/{number}','model' => '','field_type' => 'select','seeds' => '{school_acronym},{faculty},{department},{entry_mode},{programme_code},{programme_type},{session},{number},{level}','programme_type_id' => $programmeType->id,'updated_by' => NULL,'created_at' => NULL,'updated_at' => NULL),
            array('name' => 'matric_number_numbering_format','value' => 'zero_prefix','model' => '','field_type' => 'select','seeds' => 'zero_prefix,level_prefix','programme_type_id' => $programmeType->id,'updated_by' => NULL,'created_at' => NULL,'updated_at' => NULL)
        );
        DB::table('configurations')->insert($configurations);        
    }
}
