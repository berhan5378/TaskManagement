<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    protected $table ='task_management';

    protected $fillable = [
        'user_id', // UUID for user_id
        'task_name',
        'priority',
        'start_date',
        'end_date',
        'deadline',
        'status',
    ];

    // Define the relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->user_id = Auth::user()->uuid; // Assign the authenticated user's ID
            }
        });
    }

    public function isOverdue()
    {
        $deadline = Carbon::parse($this->deadline)->toDateString();
        $today = Carbon::today()->toDateString();

        return $deadline < $today && $this->status !== 'completed';
    }
}