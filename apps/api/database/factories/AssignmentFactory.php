<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * AssignmentFactory creates flipbook or recitation assignments.
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    /** definition returns mixed content assignment with rubric. */
    public function definition(): array
    {
        $isRecitation = $this->faker->boolean(40);
        $content = $isRecitation
            ? ['pages'=>[], 'hotspots'=>[], 'recitation'=>[
                'surah'=>$this->faker->numberBetween(1,114),
                'fromAyah'=>$this->faker->numberBetween(1,5),
                'toAyah'=>$this->faker->numberBetween(6,15),
              ]]
            : ['pages'=>[
                ['url'=>'https://placehold.co/800x1100?text=Quran+Page+'.$this->faker->numberBetween(1,3)],
              ],
              'hotspots'=>[
                ['pageIndex'=>0,'x'=>100,'y'=>140,'w'=>160,'h'=>60,'title'=>'Practice','tooltip'=>'Focus here'],
              ],
              'recitation'=>null,
            ];

        return [
            'title' => $isRecitation ? 'Recite Surah '.$content['recitation']['surah'] : 'Flipbook Practice '.$this->faker->randomElement(['A','B']),
            'description' => $this->faker->sentence(10),
            'class_id' => SchoolClass::inRandomOrder()->first()?->id,
            'teacher_id' => User::role('teacher')->inRandomOrder()->first()?->id,
            'status' => $this->faker->randomElement(['published','draft']),
            'due_at' => now()->addDays($this->faker->numberBetween(3,10)),
            'content' => $content,
            'grading' => [
                'rubric'=>['tajweed'=>40,'fluency'=>30,'memory'=>30],
                'auto_ai'=>true
            ],
            'distribution' => ['class_id'=>null,'student_ids'=>[]],
        ];
    }


}