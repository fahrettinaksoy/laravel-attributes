<?php

declare(strict_types=1);

use App\Http\Controllers\Common\CommonController;
use App\Http\Controllers\Parameter\ParameterController;
use App\Http\Middleware\ResolveModelFromRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api', ResolveModelFromRoute::class])->group(function () {

    Route::name('parameter.')->prefix('parameter')->group(function () {
        Route::get('/', [ParameterController::class, 'index'])->name('index');
    });

    Route::name('common.')->group(function () {
        Route::get('{path}/{id}', [CommonController::class, 'show'])->name('show')->where('path', '.*')->where('id', '[0-9]+');
        Route::put('{path}/{id}', [CommonController::class, 'update'])->name('update')->where('path', '.*')->where('id', '[0-9]+');

        Route::delete('{path}/{id}', [CommonController::class, 'destroy'])->name('destroy.single')->where('path', '.*')->where('id', '[0-9]+');
        Route::delete('{path}', [CommonController::class, 'destroy'])->name('destroy')->where('path', '.*');

        Route::get('{path}/{parent_id}/{relation}/{relation_id}', [CommonController::class, 'pivotShow'])->name('pivot.show')->where('path', '.*')->where('parent_id', '[0-9]+')->where('relation', '[a-zA-Z_]+')->where('relation_id', '[0-9]+');
        Route::put('{path}/{parent_id}/{relation}/{relation_id}', [CommonController::class, 'pivotUpdate'])->name('pivot.update')->where('path', '.*')->where('parent_id', '[0-9]+')->where('relation', '[a-zA-Z_]+')->where('relation_id', '[0-9]+');
        Route::delete('{path}/{parent_id}/{relation}/{relation_id}', [CommonController::class, 'pivotDestroy'])->name('pivot.destroy')->where('path', '.*')->where('parent_id', '[0-9]+')->where('relation', '[a-zA-Z_]+')->where('relation_id', '[0-9]+');
        Route::get('{path}/{parent_id}/{relation}', [CommonController::class, 'pivotIndex'])->name('pivot.index')->where('path', '.*')->where('parent_id', '[0-9]+')->where('relation', '[a-zA-Z_]+');
        Route::post('{path}/{parent_id}/{relation}', [CommonController::class, 'pivotStore'])->name('pivot.store')->where('path', '.*')->where('parent_id', '[0-9]+')->where('relation', '[a-zA-Z_]+');
        Route::get('{path}', [CommonController::class, 'index'])->name('index')->where('path', '.*');
        Route::post('{path}', [CommonController::class, 'store'])->name('store')->where('path', '.*');
    });

});

Route::fallback(function (Request $request) {
    return response()->json([
        'message' => 'Route not found',
        'path' => $request->path(),
        'method' => $request->method(),
    ], 404);
});
