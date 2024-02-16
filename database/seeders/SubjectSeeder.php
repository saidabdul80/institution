<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $subjects = [
            ['id'=>1, 'name'=> 'Accounting'],
            ['id'=>2, 'name'=> 'Agricultural Science'],
            ['id'=>3, 'name'=> 'Arabic Language'],
            ['id'=>4, 'name'=> 'Basic Electricity'],
            ['id'=>5, 'name'=> 'Biology'],
            ['id'=>6, 'name'=> 'Book Keeping'],
            ['id'=>7, 'name'=> 'Brick Laying and Block Laying'],
            ['id'=>8, 'name'=> 'Building And Engineering Drawing'],
            ['id'=>9, 'name'=> 'Cable Joint And Battery Charge'],
            ['id'=>10, 'name'=> 'Chemistry'],
            ['id'=>11, 'name'=> 'Class Teaching'],
            ['id'=>12, 'name'=> 'Commerce'],
            ['id'=>13, 'name'=> 'Concreating'],
            ['id'=>14, 'name'=> 'CRK'],
            ['id'=>15, 'name'=> 'Domestic And Industrial Installation'],
            ['id'=>16, 'name'=> 'Economics'],
            ['id'=>17, 'name'=> 'Education'],
            ['id'=>18, 'name'=> 'Electrical Installation and maintenance'],
            ['id'=>19, 'name'=> 'Electronic Device and Circuit'],
            ['id'=>20, 'name'=> 'English'],
            ['id'=>21, 'name'=> 'English Literature'],
            ['id'=>22, 'name'=> 'Fine Arts'],
            ['id'=>23, 'name'=> 'Fitting, Drilling and Grinding'],
            ['id'=>24, 'name'=> 'Food And Nutrition'],
            ['id'=>25, 'name'=> 'French'],
            ['id'=>26, 'name'=> 'Further Mathematics'],
            ['id'=>27, 'name'=> 'General Metal Work'],
            ['id'=>28, 'name'=> 'Geography'],
            ['id'=>29, 'name'=> 'Government'],
            ['id'=>30, 'name'=> 'Hausa Language'],
            ['id'=>31, 'name'=> 'Hausa Literature'],
            ['id'=>32, 'name'=> 'Health Science'],
            ['id'=>33, 'name'=> 'History'],
            ['id'=>34, 'name'=> 'Home Economics'],
            ['id'=>35, 'name'=> 'Home Management'],
            ['id'=>36, 'name'=> 'Igbo Language'],
            ['id'=>37, 'name'=> 'Information And Communication Tech.'],
            ['id'=>38, 'name'=> 'Int Science'],
            ['id'=>39, 'name'=> 'Intro to Building Construction'],
            ['id'=>40, 'name'=> 'Mathematics'],
            ['id'=>41, 'name'=> 'Mechanical Engineering Craft Practice'],
            ['id'=>42, 'name'=> 'Motor Vehicle M. Works'],
            ['id'=>43, 'name'=> 'Office Practice'],
            ['id'=>44, 'name'=> 'P.H.E.'],
            ['id'=>45, 'name'=> 'Physics'],
            ['id'=>46, 'name'=> 'Radio Communication'],
            ['id'=>47, 'name'=> 'Short Hand'],
            ['id'=>48, 'name'=> 'Social Studies'],
            ['id'=>49, 'name'=> 'Technical Drawing'],
            ['id'=>50, 'name'=> 'Television'],
            ['id'=>51, 'name'=> 'Turning, Milling, Shaping, Planning, and Slotting'],
            ['id'=>52, 'name'=> 'Typewriting'],
            ['id'=>53, 'name'=> 'Visual Arts'],
            ['id'=>54, 'name'=> 'Walls, Floors and Ceeling Finishing'],
            ['id'=>55, 'name'=> 'Winding Of Electrical Machine'],
            ['id'=>56, 'name'=> 'Wood Work'],
            ['id'=>57, 'name'=> 'Yoruba Language'],
            ['id'=>58, 'name'=> 'Islamic Studies'],
            ['id'=>59, 'name'=> 'Civic Education'],
            ['id'=>60, 'name'=> 'Computer Science'],
            ['id'=>61, 'name'=> 'Marketing'],
            ['id'=>62, 'name'=> 'Computer Technology'],
            ['id'=>63, 'name'=> 'Automobile Electronic'],
            ['id'=>64, 'name'=> 'Electrical and Electronic'],
            ['id'=>65, 'name'=> 'Financial Accounting'],
            ['id'=>66, 'name'=> 'Teaching Practice'],
            ['id'=>67, 'name'=> 'Automobile'],
            ['id'=>68, 'name'=> 'Metal Work'],
            ['id'=>69, 'name'=> 'Building']
        ];

        DB::table('subjects')->insert($subjects);

    }
}
