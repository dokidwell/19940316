<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ArtworkController;
use App\Http\Controllers\CreateController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WhaleController;
use App\Http\Controllers\PointsController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\GovernanceController;
use App\Http\Controllers\EcosystemController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Authentication Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');

// Redirect root to artworks (作品)
Route::get('/', function () {
    return redirect()->route('artworks.index');
});

// 作品 (Artworks) Routes
Route::prefix('artworks')->name('artworks.')->group(function () {
    Route::get('/', [ArtworkController::class, 'index'])->name('index');
    Route::get('/{id}', [ArtworkController::class, 'show'])->name('show');
});

// 創作 (Create) Routes
Route::prefix('create')->name('create.')->group(function () {
    Route::get('/', [CreateController::class, 'index'])->name('index');
    Route::post('/upload', [CreateController::class, 'upload'])->name('upload');
    Route::post('/store', [CreateController::class, 'store'])->name('store');
});

// 市場 (Market) Routes
Route::prefix('market')->name('market.')->group(function () {
    Route::get('/', [MarketController::class, 'index'])->name('index');
    Route::get('/{id}', [MarketController::class, 'show'])->name('show');
    Route::post('/{id}/purchase', [MarketController::class, 'purchase'])->name('purchase');
});

// 社區 (Community) Routes
Route::prefix('community')->name('community.')->group(function () {
    Route::get('/', [GovernanceController::class, 'index'])->name('index');
    Route::get('/proposals', [GovernanceController::class, 'proposals'])->name('proposals');
    Route::get('/proposals/history', [GovernanceController::class, 'proposalHistory'])->name('proposals.history');
    Route::get('/proposals/{id}', [GovernanceController::class, 'showProposal'])->name('proposals.show');
    Route::get('/search', [GovernanceController::class, 'searchProposals'])->name('search');

    // 需要登录的路由
    Route::middleware('auth')->group(function () {
        Route::post('/proposals', [GovernanceController::class, 'createProposal'])->name('proposals.create');
        Route::post('/proposals/{id}/vote', [GovernanceController::class, 'vote'])->name('proposals.vote');
        Route::get('/voting-history', [GovernanceController::class, 'getUserVotingHistory'])->name('voting.history');

        // 权限检查
        Route::get('/check-proposal-permission', [GovernanceController::class, 'checkProposalPermission'])->name('check.proposal');
        Route::get('/proposals/{id}/check-voting-permission', [GovernanceController::class, 'checkVotingPermission'])->name('check.voting');

        // 工具接口
        Route::post('/calculate-voting-cost', [GovernanceController::class, 'calculateVotingCost'])->name('calculate.cost');
        Route::get('/proposals/{id}/voting-distribution', [GovernanceController::class, 'getVotingDistribution'])->name('voting.distribution');

        // 管理员路由
        Route::middleware('role:admin')->group(function () {
            Route::get('/pending-proposals', [GovernanceController::class, 'getPendingProposals'])->name('pending');
            Route::post('/proposals/{id}/approve', [GovernanceController::class, 'approveProposal'])->name('proposals.approve');
            Route::post('/proposals/{id}/reject', [GovernanceController::class, 'rejectProposal'])->name('proposals.reject');
            Route::post('/proposals/{id}/finalize', [GovernanceController::class, 'finalizeProposal'])->name('proposals.finalize');
        });
    });

    // 公开统计接口
    Route::get('/stats', [GovernanceController::class, 'getGovernanceStats'])->name('stats');
});

// 我的 (Profile) Routes
Route::prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('index');
    Route::get('/settings', [ProfileController::class, 'settings'])->name('settings');
    Route::post('/checkin', [ProfileController::class, 'checkin'])->name('checkin');
    Route::post('/settings', [ProfileController::class, 'updateSettings'])->name('settings.update');
    Route::get('/points-history', [ProfileController::class, 'pointsHistory'])->name('points.history');
});

// 鲸探 (Whale) Routes
Route::prefix('whale')->name('whale.')->middleware('auth')->group(function () {
    // 鲸探账户管理
    Route::get('/', [WhaleController::class, 'index'])->name('index');
    Route::get('/bind', [WhaleController::class, 'bind'])->name('bind');
    Route::get('/redirect', [WhaleController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [WhaleController::class, 'callback'])->name('callback');
    Route::post('/unbind', [WhaleController::class, 'unbind'])->name('unbind');

    // 数据同步和奖励
    Route::post('/sync', [WhaleController::class, 'sync'])->name('sync');
    Route::post('/checkin', [WhaleController::class, 'checkin'])->name('checkin');
    Route::post('/airdrop', [WhaleController::class, 'airdrop'])->name('airdrop');

    // 数据查询
    Route::get('/collections', [WhaleController::class, 'collections'])->name('collections');
    Route::get('/stats', [WhaleController::class, 'stats'])->name('stats');
    Route::get('/tasks', [WhaleController::class, 'tasks'])->name('tasks');
    Route::get('/reward-history', [WhaleController::class, 'rewardHistory'])->name('reward.history');

    // 定价系统
    Route::get('/pricing', [WhaleController::class, 'pricing'])->name('pricing');
    Route::get('/pricing/{collectionId}', [WhaleController::class, 'collectionPricing'])->name('pricing.collection');
});

// 积分系统 (Points) Routes
Route::prefix('points')->name('points.')->middleware('auth')->group(function () {
    // 钱包和积分管理
    Route::get('/wallet', [PointsController::class, 'wallet'])->name('wallet');
    Route::get('/balance', [PointsController::class, 'balance'])->name('balance');
    Route::get('/stats', [PointsController::class, 'stats'])->name('stats');
    Route::get('/dashboard', [PointsController::class, 'dashboard'])->name('dashboard');

    // 交易历史
    Route::get('/history', [PointsController::class, 'history'])->name('history');
    Route::get('/recent-activity', [PointsController::class, 'recentActivity'])->name('recent.activity');

    // 透明公示
    Route::get('/transparency', [PointsController::class, 'transparency'])->name('transparency');
});

// 任务中心 (Task Center) Routes
Route::prefix('tasks')->name('tasks.')->middleware('auth')->group(function () {
    Route::get('/', [TaskCenterController::class, 'index'])->name('index');
    Route::post('/complete', [TaskCenterController::class, 'completeTask'])->name('complete');

    // 消费功能
    Route::get('/consumptions', [TaskCenterController::class, 'consumptions'])->name('consumptions');
    Route::post('/purchase', [TaskCenterController::class, 'purchase'])->name('purchase');
    Route::get('/my-consumptions', [TaskCenterController::class, 'myConsumptions'])->name('my-consumptions');
});

// 管理员路由
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // 经济系统管理
    Route::prefix('economic')->name('economic.')->group(function () {
        Route::get('/', [Admin\EconomicController::class, 'index'])->name('index');
        Route::get('/tasks', [Admin\EconomicController::class, 'tasks'])->name('tasks');
        Route::get('/consumptions', [Admin\EconomicController::class, 'consumptionScenarios'])->name('consumptions');
        Route::get('/stats', [Admin\EconomicController::class, 'economicStats'])->name('stats');

        // 任务管理
        Route::patch('/tasks/{task}/reward', [Admin\EconomicController::class, 'updateTaskReward'])->name('tasks.update-reward');
        Route::patch('/tasks/{task}/toggle', [Admin\EconomicController::class, 'toggleTask'])->name('tasks.toggle');

        // 消费管理
        Route::patch('/consumptions/{scenario}/price', [Admin\EconomicController::class, 'updateConsumptionPrice'])->name('consumptions.update-price');
        Route::patch('/consumptions/{scenario}/toggle', [Admin\EconomicController::class, 'toggleConsumptionScenario'])->name('consumptions.toggle');

        // 积分空投
        Route::post('/airdrop', [Admin\EconomicController::class, 'airdrop'])->name('airdrop');
    });
});

// 透明度相关扩展路由 (放在积分路由组外)
Route::prefix('transparency')->name('transparency.')->middleware('auth')->group(function () {
    Route::get('/search', [PointsController::class, 'transparencySearch'])->name('search');
    Route::get('/export', [PointsController::class, 'transparencyExport'])->name('export');
    Route::get('/governance', [PointsController::class, 'governanceActivity'])->name('governance');
    Route::get('/marketplace', [PointsController::class, 'marketplaceActivity'])->name('marketplace');
    Route::get('/whale', [PointsController::class, 'whaleActivity'])->name('whale');
});

// 文件上传 (File Upload) Routes
Route::prefix('upload')->name('upload.')->middleware('auth')->group(function () {
    // 文件上传
    Route::post('/artwork', [FileUploadController::class, 'uploadArtwork'])->name('artwork');
    Route::post('/avatar', [FileUploadController::class, 'uploadAvatar'])->name('avatar');
    Route::post('/banner', [FileUploadController::class, 'uploadBanner'])->name('banner');
    Route::post('/general', [FileUploadController::class, 'uploadGeneral'])->name('general');
    Route::post('/batch', [FileUploadController::class, 'batchUpload'])->name('batch');

    // 鲸探头像设置
    Route::post('/whale-avatar', [FileUploadController::class, 'setWhaleAvatar'])->name('whale.avatar');

    // 上传管理
    Route::get('/token', [FileUploadController::class, 'getUploadToken'])->name('token');
    Route::delete('/file', [FileUploadController::class, 'deleteFile'])->name('delete');
    Route::get('/file/{key}', [FileUploadController::class, 'getFileInfo'])->name('file.info');

    // 上传状态和统计
    Route::get('/progress/{uploadId}', [FileUploadController::class, 'getUploadProgress'])->name('progress');
    Route::get('/storage-stats', [FileUploadController::class, 'getUserStorageStats'])->name('storage.stats');

    // 配置和验证
    Route::get('/config', [FileUploadController::class, 'getUploadConfig'])->name('config');
    Route::post('/validate', [FileUploadController::class, 'validateFile'])->name('validate');
});

// 短信验证 (SMS) Routes
Route::prefix('sms')->name('sms.')->group(function () {
    // 公开路由（无需登录）
    Route::post('/send-code', [SmsController::class, 'sendVerificationCode'])->name('send.code');
    Route::post('/verify-code', [SmsController::class, 'verifyCode'])->name('verify.code');
    Route::post('/resend-code', [SmsController::class, 'resendVerificationCode'])->name('resend.code');
    Route::post('/validate-phone', [SmsController::class, 'validatePhone'])->name('validate.phone');
    Route::get('/verification-status', [SmsController::class, 'getVerificationStatus'])->name('verification.status');

    // 需要登录的路由
    Route::middleware('auth')->group(function () {
        Route::post('/bind-phone', [SmsController::class, 'bindPhone'])->name('bind.phone');
        Route::post('/unbind-phone', [SmsController::class, 'unbindPhone'])->name('unbind.phone');
        Route::get('/user-info', [SmsController::class, 'getUserSmsInfo'])->name('user.info');

        // 管理员路由
        Route::middleware('role:admin')->group(function () {
            Route::post('/send-notification', [SmsController::class, 'sendNotification'])->name('send.notification');
            Route::post('/batch-send', [SmsController::class, 'batchSendSms'])->name('batch.send');
            Route::get('/stats', [SmsController::class, 'getSmsStats'])->name('stats');
            Route::post('/clear-lock', [SmsController::class, 'clearPhoneLock'])->name('clear.lock');
        });
    });
});

// 生態系統 (Ecosystem) Routes
Route::prefix('ecosystem')->name('ecosystem.')->group(function () {
    // 公開路由
    Route::get('/', [EcosystemController::class, 'index'])->name('index');
    Route::get('/governance', [EcosystemController::class, 'governance'])->name('governance');
    Route::get('/transparency', [EcosystemController::class, 'transparency'])->name('transparency');
    Route::get('/whale', [EcosystemController::class, 'whale'])->name('whale');
    Route::get('/developers', [EcosystemController::class, 'developers'])->name('developers');

    // API 路由
    Route::get('/api/stats', [EcosystemController::class, 'getStatsApi'])->name('api.stats');
    Route::get('/api/activity', [EcosystemController::class, 'getActivityApi'])->name('api.activity');

    // 需要登錄的路由
    Route::middleware('auth')->group(function () {
        Route::get('/tasks', [EcosystemController::class, 'tasks'])->name('tasks');
    });
});
