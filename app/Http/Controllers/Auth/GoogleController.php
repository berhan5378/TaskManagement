<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use GuzzleHttp\Client;
use Exception;

class GoogleController extends Controller
{
    /**
     * Redirect to Google for authentication.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google authentication callback.
     */
    public function handleGoogleCallback()
    {
        try {
            // Set custom Guzzle client to handle SSL verification issues (temporary fix)
            $client = new Client(['verify' => false]); 
            Socialite::driver('google')->setHttpClient($client);

            // Retrieve user from Google
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser->getEmail()) {
                return redirect()->route('login')->withErrors(['error' => 'Google account does not have an email.']);
            }

            // Check if the user already exists
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) { 
                $user = User::create([
                    'name'     => $googleUser->getName(),
                    'email'    => $googleUser->getEmail(),
                    'password' => bcrypt(uniqid()), // Set a random password
                ]);
            }

            // Log the user in
            Auth::login($user);

            return redirect()->route('taskManagement.index');
        } catch (\GuzzleHttp\Exception\ClientException $e) { 
            return redirect()->route('login')->withErrors(['error' => 'Google authentication failed.']);
        } catch (Exception $e) { 
            return redirect()->route('login')->withErrors(['error' => 'Something went wrong. Please try again later.']);
        }
    }
}
