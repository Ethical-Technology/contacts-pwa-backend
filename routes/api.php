<?php

use Illuminate\Http\Request;
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


Route::post('register', 'UserController@register');
Route::post('login', 'UserController@authenticate');
Route::get('open', 'DataController@open');
Route::any('/sms-controlling', 'TwilioController@smsControlling')->name('smscon');
//Route::get('sync-contacts', 'ContactController@syncContacts');
Route::any('/zoho-auth', 'ZohoController@zohoAuth');

Route::group(['middleware' => ['jwt.verify']], function() {

    Route::get('user', 'UserController@getAuthenticatedUser');
    Route::post('update-profile', 'UserController@saveProfile');
    Route::post('update-profile-image', 'UserController@updateProfileImage');
    Route::get('get-user', 'UserController@getAuthenticatedUser');



    Route::post('save-contacts', 'ContactController@store');
    Route::get('contacts-list', 'ContactController@contactsList');
    Route::post('delete-contact', 'ContactController@delPros');
    Route::post('edit-contact', 'ContactController@edit');
    Route::post('fetch-chat', 'ContactController@fetch_chat');
    Route::post('send-sms', 'ContactController@ChatSend');
    Route::get('sync-contacts', 'ContactController@syncContacts');

    Route::get('get-settings', 'SettingController@index');
    Route::post('store-settings', 'SettingController@store');

    Route::get('closed', 'DataController@closed');
    Route::post("logout","UserController@logout");
    
});



// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
