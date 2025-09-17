@extends('layouts.app')

@section('title', '社區 - HOHO')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12 fade-in-up">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                <span class="gradient-text">HOHO 社區</span>
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                與創作者互動，參與討論，共同建設更好的創意社群
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Community Stats -->
                <div class="card p-6 fade-in-up">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">社區數據</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold gradient-text">15,247</div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">活躍用戶</p>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold gradient-text">3,891</div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">討論主題</p>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold gradient-text">28,456</div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">作品分享</p>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold gradient-text">156K</div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">互動次數</p>
                        </div>
                    </div>
                </div>

                <!-- Governance Section -->
                <div class="card p-6 fade-in-up" style="animation-delay: 0.1s;">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">社區治理</h2>
                        <button class="btn-primary text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            提出提案
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Active Proposal -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-1">
                                        提案 #001: 調整創作者分成比例
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        建議將創作者分成從 70% 調整至 75%，以更好地激勵優秀創作者
                                    </p>
                                </div>
                                <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium rounded-full">
                                    進行中
                                </span>
                            </div>

                            <!-- Voting Progress -->
                            <div class="mb-3">
                                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300 mb-1">
                                    <span>支持: 12,847 HOHO (67.3%)</span>
                                    <span>反對: 6,234 HOHO (32.7%)</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: 67.3%"></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    剩餘時間: 2 天 14 小時
                                </div>
                                <div class="flex gap-2">
                                    <button class="px-4 py-2 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-sm font-medium rounded-lg hover:bg-green-200 dark:hover:bg-green-800 transition-colors duration-200">
                                        支持 (100 HOHO)
                                    </button>
                                    <button class="px-4 py-2 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-sm font-medium rounded-lg hover:bg-red-200 dark:hover:bg-red-800 transition-colors duration-200">
                                        反對 (100 HOHO)
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Past Proposals -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-1">
                                        提案 #000: 建立社區規範
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        制定社區行為準則和內容發布規範
                                    </p>
                                </div>
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-medium rounded-full">
                                    已通過
                                </span>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                通過率: 89.2% | 3 天前結束
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transparency Board -->
                <div class="card p-6 fade-in-up" style="animation-delay: 0.2s;">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">透明公示</h2>
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            自動更新：每 5 分鐘
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-900 dark:text-white">用戶 hoho#12847 獲得簽到積分 +50.0</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">2 分鐘前</span>
                        </div>

                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-900 dark:text-white">作品 "數位夢境" 鑄造為 NFT #0001</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">5 分鐘前</span>
                        </div>

                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-purple-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-900 dark:text-white">提案 #001 收到新投票 (支持)</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">7 分鐘前</span>
                        </div>

                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-yellow-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-900 dark:text-white">用戶 hoho#98765 暱稱失效 (藏品已轉讓)</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">12 分鐘前</span>
                        </div>

                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-red-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-900 dark:text-white">管理員審核操作：批准作品 "星空幻想"</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">15 分鐘前</span>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <button class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                            查看完整記錄
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Community Guidelines -->
                <div class="card p-6 fade-in-up" style="animation-delay: 0.3s;">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">社區規範</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-600 dark:text-gray-300">尊重他人創作與觀點</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-600 dark:text-gray-300">發布原創內容</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-600 dark:text-gray-300">積極參與社區建設</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-red-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span class="text-gray-600 dark:text-gray-300">禁止惡意攻擊和騷擾</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-red-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span class="text-gray-600 dark:text-gray-300">禁止發布違法內容</span>
                        </div>
                    </div>
                </div>

                <!-- Active Contributors -->
                <div class="card p-6 fade-in-up" style="animation-delay: 0.4s;">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">活躍貢獻者</h3>
                    <div class="space-y-3">
                        @for($i = 1; $i <= 5; $i++)
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mr-3">
                                <span class="text-white text-sm font-bold">{{ $i }}</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">hoho#{{ str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ rand(500, 9999) }} 積分</p>
                            </div>
                        </div>
                        @endfor
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card p-6 fade-in-up" style="animation-delay: 0.5s;">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">快速操作</h3>
                    <div class="space-y-3">
                        <button class="w-full btn-secondary text-left">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            提出新提案
                        </button>
                        <button class="w-full btn-secondary text-left">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            查看歷史提案
                        </button>
                        <button class="w-full btn-secondary text-left">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            意見反饋
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-refresh transparency board
setInterval(function() {
    // Simulate new activity
    const activityList = document.querySelector('.space-y-3');
    if (activityList) {
        // Add visual indicator for new updates
        const indicator = document.querySelector('.text-gray-500.dark\\:text-gray-400');
        if (indicator) {
            indicator.textContent = '剛剛更新';
            setTimeout(() => {
                indicator.textContent = '自動更新：每 5 分鐘';
            }, 2000);
        }
    }
}, 30000); // Update every 30 seconds for demo

// Voting buttons
document.addEventListener('DOMContentLoaded', function() {
    const voteButtons = document.querySelectorAll('button[class*="bg-green-"], button[class*="bg-red-"]');
    voteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const isSupport = this.classList.contains('bg-green-100') || this.classList.contains('bg-green-900');
            const currentVotes = parseInt(this.textContent.match(/\\d+/)[0]);

            // Simulate voting
            this.disabled = true;
            this.textContent = isSupport ? `已支持 (${currentVotes})` : `已反對 (${currentVotes})`;
            this.classList.add('opacity-50');

            // Show success message
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            toast.textContent = '投票成功！';
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        });
    });
});
</script>
@endpush
@endsection