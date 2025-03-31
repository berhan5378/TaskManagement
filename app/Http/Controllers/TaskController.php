<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Exception;
use App\Http\Requests\SaveTaskRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{ 
    protected $tasks;
    protected $totalTasks;
    protected $completedTasks;
    protected $pendingTasks;
    protected $inProgressTasks;
    protected $overdueTasks;

    public function __construct(Task $tasks)
    {
        try {
            // Fetch tasks for the authenticated user
            $tasks = $tasks::where('user_id', Auth::user()->uuid)->get();

            // Calculate task counts
            $this->totalTasks = $tasks->count();
            $this->completedTasks = $tasks->where('status', 'completed')->count();
            $this->pendingTasks = $tasks->where('status', 'pending')->count();
            $this->inProgressTasks = $tasks->where('status', 'in_progress')->count();

            // Calculate overdue tasks
            $this->overdueTasks = $tasks->where('deadline', '<', Carbon::today()->toDateString())
                ->where('status', '!=', 'completed')
                ->count(); 

            $this->tasks = $tasks->map(function ($task) { 
                $task->start_date = date('M j, Y', strtotime($task->start_date));
                $task->deadline = date('M j, Y', strtotime($task->deadline));
                $task->end_date = $task->end_date ? date('M j, Y', strtotime($task->end_date)) : "-";
                return $task;
            });
            
        } catch (Exception $e) {
            return redirect()->route('taskManagement')->with('task_status', 'Failed to show task. Please try again.');
        }
    }
    // Display a listing of tasks
    public function index()
    {  
        return view('taskManagement.index', [
            'tasks' => $this->tasks,
            'tasksOverview'=>[
                'totalTasks' => $this->totalTasks,
                'completedTasks' => $this->completedTasks,
                'pendingTasks' => $this->pendingTasks,
                'inProgressTasks' => $this->inProgressTasks,
                'overdueTasks' => $this->overdueTasks,
            ],
            'pieChart' => json_encode([
                'labels' => ['Completed', 'Pending', 'Overdue', 'In Progress'],
                'data' => [ $this->completedTasks, $this->pendingTasks, $this->overdueTasks, $this->inProgressTasks],
            ]),
        ]);
    }

    public function DateRange(Request $request)
    {
        try{ 
            $request->validate([
                'dateRange' => 'required|integer|digits:4|min:1900|max:' . date('Y'), 
            ]); 
            // Get the validated year or default to the current year
            $year = $request->input('dateRange', date('Y'));
             
            // Fetch tasks grouped by month for the specified year
            $tasksPerPeriod = Task::where('user_id', Auth::user()->uuid)
                ->selectRaw('MONTH(created_at) as period, COUNT(*) as count')
                ->where('status', 'completed')
                ->whereYear('created_at', $year)
                ->groupBy('period')
                ->orderBy('period')
                ->pluck('count', 'period')
                ->toArray();
 
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            $tasksCompletedData = [];
            $maxPeriods = 12;
            
            for ($period = 1; $period <= $maxPeriods; $period++) {
                $tasksCompletedData[] = $tasksPerPeriod[$period] ?? 0;
            }
        
            return response()->json([
                'barChart' => [
                    'labels' => $labels,
                    'data' => $tasksCompletedData,
                ],
            ]);
            
        } catch (ValidationException $e) {
            return  response()->json(collect($e->errors())->flatten()->first()); // Get only the first error
        } catch (Exception $e) { 
            return response()->json('Failed to show tasks of year'); 
        }
    }

     // Show the form for creating a new task
     public function create()
     {
        $tasks=$this->tasks;
        return view('taskManagement.createTask', compact('tasks'));
     } 

     // Store a newly created task in the database
    public function store(SaveTaskRequest $request)
    {
        try {
            Task::create($request->validated());
            return "Task created successfully";
        } catch (Exception $e) { 
            return "Failed to create task. Please try again";
        }
    } 

    // Show the form for editing the specified task
    public function edit(Task $task)
    {
        $tasks=$this->tasks; 
        return view('taskManagement.editTask', compact('task','tasks'));
    }

     // Update the specified task in the database
     public function update(SaveTaskRequest $request, Task $task)
     {  
         try {
             // Set end_date only if status is 'completed' and it was previously null
             $updateData = $request->validated();
     
             if ($request->status === 'completed' && $task->end_date === null) {
                 $updateData['end_date'] = now();
             }
     
             // Update the task
             $task->update($updateData);
     
             return "Task updated successfully";
         } catch (Exception $e) {
             return "Failed to update task. Please try again later";
         }
     }

     // Remove the specified task from the database
     public function destroy(Task $task)
     {
         try {
             $task->delete();
             return redirect()->route('taskManagement.create')->with('status', 'Task deleted successfully.');
         } catch (\Exception $e) {
             return redirect()->route('taskManagement.create')->with('error', 'Failed to delete task.');
         }
     }
     
}
