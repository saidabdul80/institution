<?php

namespace App\Repositories;

use App\Models\Course;
use App\Models\Session;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class StudentRepository
{
    protected $student;

    public function __construct(Student $student)
    {
        $this->student = $student;
    }

    public function getById($id)
    {
        return $this->student->find($id);
    }

    public function getStudentWithResult($student_id, $session_id, $semester_id)
    {
        $prev_session = Session::where('id', '<', $session_id)->orderBy('id', 'desc')->first();
        if ($semester_id == 1)
        {
            $prev_session_id = $prev_session->id;
            $prev_semester = 2;
        }
        else
        {
            $prev_session_id = $session_id;
            $prev_semester = 1;
        }
        // temporary fix for coeminna
        if (tenant('sub_domain') == 'coeminna') {
            $result =  $this->student->where('id', $student_id)->with(['student_courses_grades' => function ($q) use ($session_id, $semester_id) {
                $q->where('session_id', $session_id);
                $q->where('semester_id', $semester_id);
                $q->where('status', 'published');
            }])->first();
            $sresult = $this->getCOEMinnaResult($session_id, $student_id, $semester_id);
            $result->previous_result = collect([
                'tgp' => $sresult?->cum_ptgpe,
                'cgpa' => $sresult?->cum_pcgpa,
                'tcur' => $sresult?->cum_ptcur,
                'tcue' => $sresult?->cum_ptcue,
            ]);
            $result->latest_result = collect([
                'tgp' => $sresult?->cum_tgpe,
                'cgpa' => $sresult?->cum_cgpa,
                'tcur' => $sresult?->cum_tcur,
                'tcue' => $sresult?->cum_tcue,
            ]);
            $failed = $this->getCOEMinnaFailedCourses($session_id, $student_id, $semester_id);
            $result->cos = $failed;
            $result->oc_count = $failed?->count();
            $result->remark = $sresult?->remark2;
            return $result;
        }

        return $this->student->where('id', $student_id)->with(['student_courses_grades' => function ($q) use($session_id, $semester_id) {
            $q->where('session_id', $session_id);
            $q->where('semester_id', $semester_id);
            $q->where('status', 'published');
        }])->with(['latest_result' => function ($query) {
            $query->where('result_type', 'cumulative')->latest();
        }])->with(['previous_result' => function ($query1) use($prev_session, $semester_id, $session_id) {
            if ($semester_id == 1)
            {
                $query1->where('session_id', $prev_session->id);
                $query1->where('semester_id', 2);
            }else {
                $query1->where('session_id', $session_id);
                $query1->where('semester_id', 1);
            }
            $query1->where('status', 'published');
        }])->with(['result' => function ($query2) use($session_id, $semester_id) {
            $query2->where('result_type', 'cumulative')->where('session_id', $session_id);
            $query2->where('semester_id', $semester_id);
            $query2->where('status', 'published');
        }])->with(['cos' => function ($q1) {
            $q1->where('status', 'published');
            $q1->where('grade_status', 'failed');
        }])->first();
    }

    public function getCOEMinnaResult($session_id, $student_id, $semester_id)
    {
        $actual_student = $this->student->find($student_id);
        $student_id = DB::table('jdlabser_coeminna_dbx4.students_nce')->where('number', $actual_student->matric_number)->first()?->id;
        return DB::table('jdlabser_coeminna_dbx4.students_results_nce')->selectRaw("cum_pcgpa, cum_ptcur, cum_ptgpe, cum_ptcue, prev_gpa, cum_tcur, cum_tgpe, cum_tcue,cum_cgpa, remark2, remark1")
        ->where([
            'session_id' => $session_id,
            'student_id' => $student_id,
            'semester' => $semester_id,
            'status' => '0'
        ])->first();
    }

    public function getCOEMinnaFailedCourses($session_id, $student_id, $semester_id)
    {
        $actual_student = $this->student->find($student_id);
        $student_id = DB::table('jdlabser_coeminna_dbx4.students_nce')->where('number', $actual_student->matric_number)->first()?->id;
        $course_ids = DB::table('jdlabser_coeminna_dbx4.students_results_nce')->select("remark1")
        ->where([
            'session_id' => $session_id,
            'student_id' => $student_id,
            'semester' => $semester_id,
            'status' => '0'
        ])->first();
        return Course::whereIn('id', explode(',', $course_ids->remark1))->get();
    }

    public function getCOEMinnaPassedCourses($session_id, $student_id, $semester_id)
    {
        $actual_student = $this->student->find($student_id);
        $student_id = DB::table('jdlabser_coeminna_dbx4.students_nce')->where('number', $actual_student->matric_number)->first()?->id;
        return DB::table('jdlabser_coeminna_dbx4.student_courses_nce')->selectRaw("SUM(course_unit) AS summed_up")
        ->where([
            'student_id' => $student_id,
            'session_id' => $session_id,
            'semester' => $semester_id,
            'status' => '0',
        ])->where('total_score', '>', 39)->first();
    }
}
