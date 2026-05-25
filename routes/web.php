<?php

use App\Http\Controllers\CounterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuardEntryController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\QueueLogController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/ext-assets/prclogo.png', function () {
    $candidates = [
        base_path('public/prclogo.png'),
        storage_path('app/public/prclogo.png'),
    ];
    foreach ($candidates as $path) {
        if (file_exists($path)) {
            return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
        }
    }
    abort(404);
})->name('ext.prclogo');
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::get('/register', [App\Http\Controllers\RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [App\Http\Controllers\RegisterController::class, 'register'])->name('register.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/auth/ping', [LoginController::class, 'ping'])->name('auth.ping');

    Route::get('/license/activate', [LicenseController::class, 'show'])->name('license.activate');
    Route::post('/license/activate', [LicenseController::class, 'activate'])->name('license.activate.post');
    Route::post('/license/verify-registration-password', [LicenseController::class, 'verifyRegistrationPassword'])->name('license.verify-registration-password');
    Route::post('/license/generate-token', [LicenseController::class, 'generateToken'])->name('license.generate-token');
    Route::post('/license/disable-activation', [LicenseController::class, 'disableActivation'])->name('license.disable-activation');

    Route::middleware(['licensed'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
        Route::get('/queue/stream', [App\Http\Controllers\QueueEventController::class, 'stream'])->name('queue.stream');
        Route::get('/live-queue-board', [App\Http\Controllers\QueueBoardController::class, 'index'])->name('live-queue-board');
        Route::get('/live-queue-board/data', [App\Http\Controllers\QueueBoardController::class, 'data'])->name('live-queue-board.data');

        Route::prefix('queue')->name('queue.')->group(function () {
            Route::get('/guard-entry', [GuardEntryController::class, 'index'])->name('guard-entry');
            Route::post('/guard-entry', [GuardEntryController::class, 'store'])->name('guard-entry.store');
            Route::get('/guard-summary/data', [GuardEntryController::class, 'summaryData'])->name('guard-summary.data');
            Route::get('/guard-summary', [GuardEntryController::class, 'summary'])->name('guard-summary');

            Route::middleware(['role:Administrator,Staff'])->group(function () {
                Route::get('/my-counter', [CounterController::class, 'myCounter'])->name('my-counter');
                Route::get('/my-counter/data', [CounterController::class, 'myCounterData'])->name('my-counter.data');
                Route::post('/my-counter/call', [CounterController::class, 'callNext'])->name('my-counter.call');
                Route::post('/my-counter/serve', [CounterController::class, 'serve'])->name('my-counter.serve');
                Route::post('/my-counter/complete', [CounterController::class, 'complete'])->name('my-counter.complete');
                Route::post('/my-counter/transfer', [CounterController::class, 'transfer'])->name('my-counter.transfer');
                Route::post('/my-counter/skip', [CounterController::class, 'skip'])->name('my-counter.skip');
                Route::post('/my-counter/cancel', [CounterController::class, 'cancel'])->name('my-counter.cancel');
                Route::post('/my-counter/recall-skipped', [CounterController::class, 'recallSkipped'])->name('my-counter.recall-skipped');
                Route::post('/my-counter/reannounce', [CounterController::class, 'reannounce'])->name('my-counter.reannounce');
                Route::post('/my-counter/restore-cancelled', [CounterController::class, 'restoreCancelled'])->name('my-counter.restore-cancelled');
                Route::get('/list', [CounterController::class, 'list'])->name('list');
                Route::get('/list/data', [CounterController::class, 'listData'])->name('list.data');
                Route::get('/logs', [QueueLogController::class, 'index'])->name('logs');
                Route::get('/logs/data', [QueueLogController::class, 'data'])->name('logs.data');
            });
        });
        Route::middleware(['role:Administrator'])->group(function () {
            Route::get('/libraries/transaction-types', [TransactionController::class, 'index'])->name('libraries.transaction-types');
            Route::get('/libraries/transaction-types/data', [TransactionController::class, 'data'])->name('libraries.transaction-types.data');
            Route::post('/libraries/transaction-types', [TransactionController::class, 'store'])->name('libraries.transaction-types.store');
            Route::put('/libraries/transaction-types/{transaction}', [TransactionController::class, 'update'])->name('libraries.transaction-types.update');
            Route::delete('/libraries/transaction-types/{transaction}', [TransactionController::class, 'destroy'])->name('libraries.transaction-types.destroy');

            Route::get('/libraries/windows', [CounterController::class, 'index'])->name('libraries.windows');
            Route::get('/libraries/windows/data', [CounterController::class, 'data'])->name('libraries.windows.data');
            Route::post('/libraries/windows', [CounterController::class, 'store'])->name('libraries.windows.store');
            Route::put('/libraries/windows/{user}', [CounterController::class, 'update'])->name('libraries.windows.update');
            Route::delete('/libraries/windows/{user}', [CounterController::class, 'destroy'])->name('libraries.windows.destroy');
            Route::get('/libraries/priorities', [PriorityController::class, 'index'])->name('libraries.priorities');
            Route::get('/libraries/priorities/data', [PriorityController::class, 'data'])->name('libraries.priorities.data');
            Route::post('/libraries/priorities', [PriorityController::class, 'store'])->name('libraries.priorities.store');
            Route::put('/libraries/priorities/{priority}', [PriorityController::class, 'update'])->name('libraries.priorities.update');
            Route::delete('/libraries/priorities/{priority}', [PriorityController::class, 'destroy'])->name('libraries.priorities.destroy');
        });

        Route::get('/account/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('account.profile');
        Route::patch('/account/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('account.profile.update');
        Route::put('/account/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password.update');
    });
});
