<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemeStringsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('theme_strings')->insert([
            [
                'name'=>'programmetypetext', 
                'value'=>'Program Type',            
            ],
            [
                'name'=>'logo', 
                'value'=>'',            
            ],
            [
                'name'=>'schoolshortname', 
                'value'=>'SSH',            
            ],
            [
                'name'=>'signuptext', 
                'value'=>'Sign Up',            
            ],
            [
                'name'=>'signintext', 
                'value'=>'Login',            
            ],
            [
                'name'=>'facultytext', 
                'value'=>'Faculty',            
            ],
            [
                'name'=>'departmenttext', 
                'value'=>'Department',            
            ],
            [
                'name'=>'entrymodetext', 
                'value'=>'Mode of Entry',            
            ],
            [
                'name'=>'programmetext', 
                'value'=>'Programme',            
            ],        
            [
                'name'=>'lightprimarycolor', 
                'value'=>'skyblue',            
            ],                   
            [
                'name'=>'lightsecondarycolor', 
                'value'=>'skyblue',            
            ],
            [
                'name'=>'darkprimarycolor', 
                'value'=>'skyblue',            
            ],                   
            [
                'name'=>'darksecondarycolor', 
                'value'=>'skyblue',            
            ],

        ]);
    }
}
