<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // Register User
    public function register(Request $request)
    {
        try {
            // Validate user input
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => ['required', Password::min(8)->letters()->numbers()->symbols()],
            ]); 
            // Use a database transaction for safety
            DB::beginTransaction();
    
            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
    
            // Log in the new user
            Auth::login($user);
    
            // Commit transaction
            DB::commit();
    
            return "success";
    
        } catch (ValidationException $e) {
            return  collect($e->errors())->flatten()->first(); // Get only the first error
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
    
            return 'registration failed';
        }
    }
    
    public function login(Request $request)
    {
        try {
            // Check if the request is rate limited
            $this->checkRateLimit($request);
    
            // Validate the request
            $credentials = $request->validate([
                'email'    => 'required|email',
                'password' => 'required',
            ]);
    
            // Attempt authentication
            if (Auth::attempt($credentials)) {
                // Clear rate limiter on successful login
                RateLimiter::clear($this->throttleKey($request));
                
                $request->session()->regenerate(); // Prevent session fixation attacks
                return 'success';//return for AJAX requests
            }
    
            // Increment rate limiter on failed attempt
            RateLimiter::hit($this->throttleKey($request));
    
            return 'Invalid credentials'; 
            
        } catch (ValidationException $e) {
            // Return validation errors for AJAX requests
            return  collect($e->errors())->flatten()->first();
        } catch (Exception $e) {
            return 'login failed';
        }
    }
    
    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }
    
    /**
     * Ensure the login request is not rate limited.
     */
    protected function checkRateLimit(Request $request): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($request));
            
            throw new \Exception('Too many login attempts. Please try again in '.$seconds.' seconds.');
        }
    }

    // Logout
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
    
} 