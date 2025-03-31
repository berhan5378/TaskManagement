<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class SaveTaskRequest extends FormRequest
{ 
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'task_name' => 'required',
            'priority' => 'required|string|in:low,medium,high', // Define allowed priorities
            'status' => 'required|string|in:pending,in_progress,completed', // Define allowed statuses
        ];

        if (!$this->shouldUse()) {
            $rules['deadline'] = 'required|date';
            $rules['start_date'] = 'required|date';
        }
    
        return $rules; 
    } 

    public function shouldUse(): bool
    {
       // If the request comes from the 'tasks.update' route
        return $this->routeIs('taskManagement.updateTask'); 
    }

    
// In your SaveTaskRequest class
protected function failedValidation(Validator $validator)
{
     
    throw new HttpResponseException(
        response()->json(collect($validator->errors())->flatten()->first())
    );
}

}
