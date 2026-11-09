<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;

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

// Las páginas son plantillas HTML estáticas (resources/views); se conectarán al frontend más adelante

// Panel de administración (requiere sesión)
Route::middleware('auth')->group(function ()
{
Route::view('/admin', 'admin.pages.manageHome');
Route::view('/admin/home', 'admin.pages.manageHome');
Route::view('/admin/skills', 'admin.pages.ManageSkills');
Route::view('/admin/contact', 'admin.pages.ManageContact');
});


Route::get('/login', function()
{
    return view('admin.login');
})->name('login');

//Defining routes for log in/log out actions
Route::post('/login', [AuthenticationController::class, 'login']);
Route::get('/logout', [AuthenticationController::class, 'logout'])->middleware('auth');

// Páginas públicas
Route::view('/', 'user.home');
Route::view('/home', 'user.home');
Route::view('/skills', 'user.skills');
Route::view('/contact', 'user.contact');
