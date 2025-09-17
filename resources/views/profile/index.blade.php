@extends('layouts.app')

@section('title', '我的頁面 - HOHO')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Sidebar -->
            <div class="space-y-6">
                <!-- Profile Card -->
                <div class="card p-6 text-center fade-in-up">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-white text-2xl font-bold">H</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">hoho#12847</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">創作者</p>

                    <!-- Bind Whale Explorer Account -->
                    <div class="border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 mb-4">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">連結鯨探帳號</p>
                        <button class="btn-primary text-sm">
                            綁定帳號
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-4 text-center border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div>
                            <div class="text-lg font-bold text-gray-900 dark:text-white">24</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">作品</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-gray-900 dark:text-white">156</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">粉絲</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-gray-900 dark:text-white">89</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">關注</div>
                        </div>
                    </div>
                </div>

                <!-- Points Balance -->
                <div class="card p-6 fade-in-up" style="animation-delay: 0.1s;">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">HOHO 積分</h3>
                        <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold gradient-text mb-2">2,847.59</div>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">可用積分</p>

                    <div class="space-y-2">
                        <button class="w-full btn-primary text-sm">
                            每日簽到
                        </button>
                        <button class="w-full btn-secondary text-sm">
                            查看記錄
                        </button>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card p-6 fade-in-up" style="animation-delay: 0.2s;">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">快速操作</h3>
                    <div class="space-y-2">
                        <a href="{{ route('create.index') }}" class="w-full btn-secondary text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            上傳作品
                        </a>
                        <button class="w-full btn-secondary text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            設定
                        </button>
                        <button class="w-full btn-secondary text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            幫助
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Activity Feed -->
                <div class="card p-6 fade-in-up" style="animation-delay: 0.3s;">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">活動動態</h3>

                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-green-500 rounded-full mt-3 mr-4 flex-shrink-0"></div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900 dark:text-white mb-1">
                                    您的作品 "數位夢境" 獲得了 15 個讚
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">2 小時前</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mt-3 mr-4 flex-shrink-0"></div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900 dark:text-white mb-1">
                                    每日簽到成功，獲得 50 積分
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">今天 08:30</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-purple-500 rounded-full mt-3 mr-4 flex-shrink-0"></div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900 dark:text-white mb-1">
                                    參與社區投票，消耗 100 積分
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">昨天 15:45</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full mt-3 mr-4 flex-shrink-0"></div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900 dark:text-white mb-1">
                                    新作品 "星空幻想" 已通過審核
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">昨天 10:20</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <button class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                            查看全部活動
                        </button>
                    </div>
                </div>

                <!-- My Artworks -->
                <div class="card p-6 fade-in-up" style="animation-delay: 0.4s;">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">我的作品</h3>
                        <a href="{{ route('create.index') }}" class="btn-primary text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            新作品
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @for($i = 1; $i <= 4; $i++)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden hover:shadow-lg transition-shadow duration-200">
                            <div class="aspect-w-16 aspect-h-9">
                                <div class="w-full h-48 bg-gradient-to-br from-purple-400 to-pink-600 flex items-center justify-center">
                                    <svg class="w-12 h-12 text-white opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-gray-900 dark:text-white">我的作品 {{ $i }}</h4>
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium rounded-full">
                                        已發布
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ rand(100, 999) }} 觀看</span>
                                    <span>{{ rand(10, 99) }} 讚</span>
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>

                    <div class="mt-6 text-center">
                        <button class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                            查看全部作品 (24)
                        </button>
                    </div>
                </div>

                <!-- Digital Cards Collection -->
                <div class="card p-6 fade-in-up" style="animation-delay: 0.5s;">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">我的數位卡片</h3>
                        <button class="btn-secondary text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                            </svg>
                            管理收藏
                        </button>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @for($i = 1; $i <= 8; $i++)
                        <div class="card card-hover p-4 text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                                <span class="text-white font-bold">#{{ str_pad($i, 3, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">HOHO 卡片</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ['Common', 'Rare', 'Epic'][rand(0, 2)] }}</p>
                        </div>
                        @endfor
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="card p-6 fade-in-up" style="animation-delay: 0.6s;">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">通知設定</h3>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">作品互動通知</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">當有人給您的作品點讚或留言時通知</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">社區更新</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">接收社區提案和投票相關通知</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">系統通知</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">接收系統維護和重要更新通知</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">收件地址</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">電子郵件</label>
                                <input type="email" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white" placeholder="your@email.com">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">手機號碼</label>
                                <input type="tel" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white" placeholder="+886 912345678">
                            </div>
                        </div>
                        <button class="btn-primary text-sm mt-4">
                            儲存設定
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Daily check-in
document.addEventListener('DOMContentLoaded', function() {
    const checkinBtn = document.querySelector('button:contains("每日簽到")');
    if (checkinBtn) {
        checkinBtn.addEventListener('click', function() {
            this.disabled = true;
            this.textContent = '已簽到';
            this.classList.add('opacity-50');

            // Show success toast
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            toast.textContent = '簽到成功！獲得 50 積分';
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        });
    }

    // Settings toggle
    const toggles = document.querySelectorAll('input[type="checkbox"]');
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            // Save settings to backend
            console.log('Setting changed:', this.closest('.flex').querySelector('h4').textContent, this.checked);
        });
    });
});
</script>
@endpush
@endsection