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

// Simple API health check to test if API routing works
Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is working',
        'timestamp' => now()->toISOString()
    ]);
});

Route::get('/simple-test', function () {
    return response()->json(['test' => 'simple api route works']);
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

// Public settings endpoint for frontend
Route::get('public-settings', function () {
    try {
        $settings = [];
        
        // Try to get PencilSpaces URL from settings table
        try {
            $pencilSpacesSetting = DB::table('settings')
                ->where('key', 'pencil_spaces_url')
                ->where('public', true)
                ->first();
                
            if ($pencilSpacesSetting) {
                $settings['pencil_spaces_url'] = json_decode($pencilSpacesSetting->value, true);
            }
        } catch (Exception $e) {
            // Fallback to environment variable
            $settings['pencil_spaces_url'] = env('PENCIL_SPACES_URL', 'https://pencilspaces.com');
        }
        
        // Add multiple key formats for frontend compatibility
        if (isset($settings['pencil_spaces_url'])) {
            $settings['pencilspaces_url'] = $settings['pencil_spaces_url']; // Alternative naming
            $settings['pencilSpacesUrl'] = $settings['pencil_spaces_url'];  // camelCase
            $settings['PENCIL_SPACES_URL'] = $settings['pencil_spaces_url']; // uppercase
        }
        
        // Add other public settings as needed
        $settings['app_name'] = config('app.name');
        $settings['app_url'] = config('app.url');
        
        return response()->json([
            'status' => 'success',
            'settings' => $settings
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'fallback_settings' => [
                'pencil_spaces_url' => 'https://pencilspaces.com'
            ]
        ], 500);
    }
});
