<?php

use App\Http\Controllers\EventAPIController;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('/debug-sentry', function () {
    throw new \Exception('Test Sentry error!');
});

//TODO Removed after testing jitsi components
Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/seeds/consultations/{author?}/{user?}', function ($author = null, $user = null) {
        $seed = new \EscolaLms\Consultations\Database\Seeders\ConsultationTermsSeeder($author, $user);
        $consultationTerms = $seed->run();
        return response()->json(['msg' => 'success']);
    });
});

Route::get('/seeds/consultations/{author?}/{user?}', function ($author = null, $user = null) {
    $seed = new \EscolaLms\Consultations\Database\Seeders\ConsultationTermsSeeder($author, $user);
    $consultationTerms = $seed->run();
    return response()->json(['msg' => 'success']);
});

Route::get('events', [EventAPIController::class, 'index']);

// Test routes for frontend
Route::get('test', function () {
    return response()->json([
        'message' => 'API is working!',
        'timestamp' => now(),
        'cors' => 'enabled'
    ]);
});

Route::get('settings', function () {
    return response()->json([
        'app_name' => config('app.name'),
        'app_url' => config('app.url'),
        'api_working' => true
    ]);
});

Route::get('config', function () {
    return response()->json([
        'api_version' => '1.0',
        'cors_enabled' => true,
        'status' => 'operational'
    ]);
});
