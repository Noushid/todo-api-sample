<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;
    protected $fillable = ['title', 'due_date', 'description', 'task_id', 'record_status'];

    public function sub_task(){
        return $this->hasMany(Task::class);
    }

    //observe this model being deleted and delete the child tasks
    public static function booted ()
    {
        parent::boot();

        self::deleting(function (Task $task) {
            foreach ($task->sub_task as $sub)
            {
                $sub->delete();
            }
        });

        self::updated(function (Task $task) {
            if (request()->status != null && request()->status == 'C') {
                foreach ($task->sub_task as $key => $subtask) {
                    $subtask->record_status = request()->status;
                    $subtask->save();
                }
            }
        });
    }

}
