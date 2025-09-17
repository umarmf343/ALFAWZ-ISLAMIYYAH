<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

// database/seeders/DemoClassesAndAssignmentsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Support\Arr;

/**
 * DemoClassesAndAssignmentsSeeder creates sample classes (Level 1–3),
 * enrolls students, and seeds demo assignments (flipbook + recitation).
 */
class DemoClassesAndAssignmentsSeeder extends Seeder
{
    /**
     * run builds demo classes, membership, and two assignments with content.
     */
    public function run(): void
    {
        $t1 = User::where('email','teacher1@alfawz.com')->first();
        $t2 = User::where('email','teacher2@alfawz.com')->first();
        $students = User::role('student')->get();

        // Create three levels
        $classes = [
            ['title'=>'Foundations A','level'=>1,'teacher_id'=>$t1->id],
            ['title'=>'Intermediate A','level'=>2,'teacher_id'=>$t1->id],
            ['title'=>'Advanced A','level'=>3,'teacher_id'=>$t2->id],
        ];
        
        $classModels = [];
        foreach ($classes as $c) {
            $class = ClassModel::firstOrCreate(
                ['title'=>$c['title']],
                [
                    'level'=>$c['level'], 
                    'teacher_id'=>$c['teacher_id'],
                    'description'=>'Demo class for level ' . $c['level'] . ' students'
                ]
            );
            $classModels[] = $class;
        }

        // Enroll students (round-robin)
        $i=0;
        foreach ($students as $s) {
            $classModels[$i % count($classModels)]->students()->syncWithoutDetaching([$s->id]);
            $i++;
        }

        // Flipbook assignment with hotspots
        $flipbook = Assignment::updateOrCreate(
            ['title'=>'Tajweed Hotspot Practice'],
            [
                'description'=>'Identify and practice tajweed regions on the page.',
                'class_id'=>$classModels[0]->id,
                'teacher_id'=>$t1?->id,
                'status'=>'published',
                'due_at'=>now()->addDays(7),
                'image_s3_url'=>'https://placehold.co/800x1100?text=Quran+Page+1',
            ]
        );

        // Simple recitation assignment
        $recitation = Assignment::updateOrCreate(
            ['title'=>'Surah Al-Kahf 1–10'],
            [
                'description'=>'Recite Surah Al-Kahf ayah 1 to 10.',
                'class_id'=>$classModels[1]->id,
                'teacher_id'=>$t1?->id,
                'status'=>'published',
                'due_at'=>now()->addDays(5),
            ]
        );

        // Seed a demo submission to visualize teacher flows
        $student = $students->first();
        if ($student && $recitation) {
            Submission::updateOrCreate(
                ['assignment_id'=>$recitation->id, 'student_id'=>$student->id],
                [
                    'status'=>'pending',
                    'score'=>null,
                    'rubric_json'=>['note'=>'Demo submission awaiting AI grading'],
                ]
            );
        }
    }
}