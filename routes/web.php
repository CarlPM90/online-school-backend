<?php


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use EscolaLms\Payments\Facades\Payments;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

Route::get('/email', function () {
    return 'Your email is now verified';
});

Route::get('/clear-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    
    return response()->json([
        'message' => 'All caches cleared!',
        'cors_paths' => config('cors.paths'),
        'cors_origins' => config('cors.allowed_origins'),
        'timestamp' => now()
    ]);
});

Route::get('/setup', function () {
    return view('setup');
});

Route::get('/setup-database', [App\Http\Controllers\SetupController::class, 'setupDatabase']);

if (App::environment(['local', 'staging', 'testing'])) {
    Route::get('/stripe-test', function () {
        return View::make('stripe-test', ['stripe_publishable_key' => Env::get('PAYMENTS_STRIPE_PUBLISHABLE_KEY', 'pk_test_51Ig8icJ9tg9t712TnCR6sKY9OXwWoFGWH4ERZXoxUVIemnZR0B6Ei0MzjjeuWgOzLYKjPNbT8NbG1ku1T2pGCP4B00GnY0uusI')]);
    });
}
