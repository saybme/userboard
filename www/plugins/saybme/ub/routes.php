<?php

use Saybme\Ub\Classes\Auth\AuthClass;
use Saybme\Ub\Classes\Auth\FilesClass;
use Saybme\Ub\Classes\Document\DocumentClass;

Route::get('api/auth/logout', function() {
    return redirect('/')->withCookie(Cookie::forget('auth'));
});


// Загружаем фото
// Route::post('api/cabinet/add/photo', function() {

//     $q = new AuthClass;
//     $user = $q->getActiveUser();   

//     Log::error($user);    

//     $file = new System\Models\File;
//     $file->data = files('file');
//     $file->is_public = true;
//     $file->save();
    
//     $user->files()->add($file);
    
// })->middleware('web');

// Загружаем фото
Route::post('api/photo/add', function() {
    $q = new FilesClass;      
    return $q->add();      
})->middleware('web');
