<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LearningGoal extends Model
{
    protected $table = 'learner_learning_goals';
    protected $fillable = ['user_id', 'framework', 'target', 'target_date'];

    protected function casts(): array
    {
        return ['target_date' => 'date'];
    }

    public static function targets(): array
    {
        return [
            'cefr' => array_combine(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'], ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']),
            'toeic' => ['250' => '250+', '450' => '450+', '550' => '550+', '650' => '650+', '750' => '750+', '850' => '850+', '900' => '900+', '990' => '990'],
            'ielts' => ['4.0' => 'Band 4.0', '4.5' => 'Band 4.5', '5.0' => 'Band 5.0', '5.5' => 'Band 5.5', '6.0' => 'Band 6.0', '6.5' => 'Band 6.5', '7.0' => 'Band 7.0', '7.5' => 'Band 7.5', '8.0' => 'Band 8.0', '8.5' => 'Band 8.5', '9.0' => 'Band 9.0'],
        ];
    }

    public function label(): string
    {
        return strtoupper($this->framework).' '.(self::targets()[$this->framework][$this->target] ?? $this->target);
    }
}
