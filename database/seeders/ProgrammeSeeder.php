<?php

namespace Database\Seeders;

use App\Models\Programme;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgrammeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $programmes = array(
            array('id' => '15','option_selection_level_id' => '1','faculty_id' => '1','department_id' => '1','programme_type_id' => '1','entry_mode_id' => '1','name' => 'Community Health Extension Worker','code' => 'CHEW','maximum_credit_unit' => '15','minimum_credit_unit' => '10','max_duration' => '3','graduation_level_id' => '3','tp_max_carry_over' => '3','duration' => '3','required_subjects' => '','accepted_grades' => 'A,B,C','study_mode' => 'Full Time','status' => 'Active','department_combination' => NULL,'min_credit_unit_req' => NULL,'deleted_at' => NULL,'deleted_by' => NULL,'created_at' => NULL,'updated_at' => NULL),
            array('id' => '16','option_selection_level_id' => '1','faculty_id' => '1','department_id' => '1','programme_type_id' => '1','entry_mode_id' => '1','name' => 'Junior Community Health Extension Workers','code' => 'JCHEW','maximum_credit_unit' => '15','minimum_credit_unit' => '10','max_duration' => '3','graduation_level_id' => '3','tp_max_carry_over' => '3','duration' => '3','required_subjects' => '','accepted_grades' => 'A,B,C','study_mode' => 'Full Time','status' => 'Active','department_combination' => NULL,'min_credit_unit_req' => NULL,'deleted_at' => NULL,'deleted_by' => NULL,'created_at' => NULL,'updated_at' => NULL),
            array('id' => '17','option_selection_level_id' => '1','faculty_id' => '1','department_id' => '1','programme_type_id' => '1','entry_mode_id' => '1','name' => 'National Diploma in Health Information Management','code' => 'NDHIM','maximum_credit_unit' => '15','minimum_credit_unit' => '10','max_duration' => '3','graduation_level_id' => '3','tp_max_carry_over' => '3','duration' => '3','required_subjects' => '','accepted_grades' => 'A,B,C','study_mode' => 'Full Time','status' => 'Active','department_combination' => NULL,'min_credit_unit_req' => NULL,'deleted_at' => NULL,'deleted_by' => NULL,'created_at' => NULL,'updated_at' => NULL),
            array('id' => '18','option_selection_level_id' => '1','faculty_id' => '1','department_id' => '1','programme_type_id' => '1','entry_mode_id' => '1','name' => 'Diploma in Health Education and Promotion','code' => 'DHEP','maximum_credit_unit' => '15','minimum_credit_unit' => '10','max_duration' => '3','graduation_level_id' => '3','tp_max_carry_over' => '3','duration' => '3','required_subjects' => '','accepted_grades' => 'A,B,C','study_mode' => 'Full Time','status' => 'Active','department_combination' => NULL,'min_credit_unit_req' => NULL,'deleted_at' => NULL,'deleted_by' => NULL,'created_at' => NULL,'updated_at' => NULL),
            array('id' => '19','option_selection_level_id' => '1','faculty_id' => '1','department_id' => '1','programme_type_id' => '1','entry_mode_id' => '1','name' => 'Environmental Health Technologist','code' => 'EHT','maximum_credit_unit' => '15','minimum_credit_unit' => '10','max_duration' => '3','graduation_level_id' => '3','tp_max_carry_over' => '3','duration' => '3','required_subjects' => '','accepted_grades' => 'A,B,C','study_mode' => 'Full Time','status' => 'Active','department_combination' => NULL,'min_credit_unit_req' => NULL,'deleted_at' => NULL,'deleted_by' => NULL,'created_at' => NULL,'updated_at' => NULL),
            array('id' => '20','option_selection_level_id' => '1','faculty_id' => '1','department_id' => '1','programme_type_id' => '1','entry_mode_id' => '1','name' => 'Medical Laboratory Technician','code' => 'MLT','maximum_credit_unit' => '15','minimum_credit_unit' => '10','max_duration' => '3','graduation_level_id' => '3','tp_max_carry_over' => '3','duration' => '3','required_subjects' => '','accepted_grades' => 'A,B,C','study_mode' => 'Full Time','status' => 'Active','department_combination' => NULL,'min_credit_unit_req' => NULL,'deleted_at' => NULL,'deleted_by' => NULL,'created_at' => NULL,'updated_at' => NULL),
            array('id' => '21','option_selection_level_id' => '1','faculty_id' => '1','department_id' => '1','programme_type_id' => '1','entry_mode_id' => '1','name' => 'Pharmacy Technician','code' => 'PT','maximum_credit_unit' => '15','minimum_credit_unit' => '10','max_duration' => '3','graduation_level_id' => '3','tp_max_carry_over' => '3','duration' => '3','required_subjects' => '','accepted_grades' => 'A,B,C','study_mode' => 'Full Time','status' => 'Active','department_combination' => NULL,'min_credit_unit_req' => NULL,'deleted_at' => NULL,'deleted_by' => NULL,'created_at' => NULL,'updated_at' => NULL)
          );
          Programme::insert($programmes);
    }
}
