@extends('layouts.app')

@section('title', 'HOHO生態系統 - 創意藝術平台')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900">
    <!-- Hero Section -->
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-purple-600/20 dark:from-blue-400/10 dark:to-purple-400/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16">
            <div class="text-center scroll-reveal">
                <h1 class="text-5xl md:text-6xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-6">
                    HOHO 生態系統
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto mb-8">
                    探索去中心化創意社區的無限可能，與全球創作者共建數位藝術的未來
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="#overview" class="btn-primary" data-sound="nav-click">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        探索生態
                    </a>
                    <a href="{{ route('ecosystem.governance') }}" class="btn-secondary" data-sound="nav-click">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        社區治理
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Overview -->
    <div id="overview" class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 scroll-reveal">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">生態統計概覽</h2>
                <p class="text-gray-600 dark:text-gray-300">實時監控平台健康狀況與社區活力</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="card scroll-reveal p-6 text-center group">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ number_format($stats['total_users']) }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">註冊用戶</p>
                    <div class="text-xs text-green-600 dark:text-green-400 mt-1">
                        +{{ number_format($stats['active_users_24h']) }} 活躍(24h)
                    </div>
                </div>

                <div class="card scroll-reveal p-6 text-center group">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ number_format($stats['total_points_circulating'], 0) }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">流通積分</p>
                    <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                        {{ number_format($stats['total_transactions']) }} 筆交易
                    </div>
                </div>

                <div class="card scroll-reveal p-6 text-center group">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ number_format($stats['active_proposals']) }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">活躍提案</p>
                    <div class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        {{ number_format($stats['total_governance_participation']) }} 參與投票
                    </div>
                </div>

                <div class="card scroll-reveal p-6 text-center group">
                    <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ number_format($stats['whale_connected_users']) }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">鯨探用戶</p>
                    <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                        NFT 生態集成
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ecosystem Features -->
    <div class="py-16 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 scroll-reveal">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">生態功能模組</h2>
                <p class="text-gray-600 dark:text-gray-300">全方位的創意社區生態系統</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- 社區治理 -->
                <div class="card scroll-reveal p-8 text-center group hover:shadow-2xl">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">社區治理</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">通過去中心化提案與二次方投票參與平台決策</p>
                    <a href="{{ route('ecosystem.governance') }}" class="btn-secondary group-hover:scale-105 transition-transform duration-200" data-sound="nav-click">
                        探索治理
                    </a>
                </div>

                <!-- 積分透明度 -->
                <div class="card scroll-reveal p-8 text-center group hover:shadow-2xl">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">積分透明度</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">完全透明的積分系統，所有交易公開可查</p>
                    <a href="{{ route('ecosystem.transparency') }}" class="btn-secondary group-hover:scale-105 transition-transform duration-200" data-sound="nav-click">
                        查看透明度
                    </a>
                </div>

                <!-- 鯨探集成 -->
                <div class="card scroll-reveal p-8 text-center group hover:shadow-2xl">
                    <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">鯨探集成</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">連接支付寶鯨探NFT，獲得動態積分獎勵</p>
                    <a href="{{ route('ecosystem.whale') }}" class="btn-secondary group-hover:scale-105 transition-transform duration-200" data-sound="nav-click">
                        連接鯨探
                    </a>
                </div>

                <!-- 任務中心 -->
                <div class="card scroll-reveal p-8 text-center group hover:shadow-2xl">
                    <div class="w-16 h-16 bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">任務中心</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">完成社區任務，獲得積分獎勵與特殊徽章</p>
                    @auth
                        <a href="{{ route('ecosystem.tasks') }}" class="btn-secondary group-hover:scale-105 transition-transform duration-200" data-sound="nav-click">
                            查看任務
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary group-hover:scale-105 transition-transform duration-200" data-sound="nav-click">
                            登入查看
                        </a>
                    @endauth
                </div>

                <!-- 開發者API -->
                <div class="card scroll-reveal p-8 text-center group hover:shadow-2xl">
                    <div class="w-16 h-16 bg-gradient-to-r from-gray-500 to-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">開發者API</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">開放的API接口，讓第三方應用輕鬆集成</p>
                    <a href="{{ route('ecosystem.developers') }}" class="btn-secondary group-hover:scale-105 transition-transform duration-200" data-sound="nav-click">
                        API文檔
                    </a>
                </div>

                <!-- 社區活動 -->
                <div class="card scroll-reveal p-8 text-center group hover:shadow-2xl">
                    <div class="w-16 h-16 bg-gradient-to-r from-rose-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">社區活動</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">定期舉辦創作大賽與社區聚會活動</p>
                    <button class="btn-secondary group-hover:scale-105 transition-transform duration-200" data-sound="nav-click" onclick="showNotification('功能開發中，敬請期待！', 'info')">
                        即將推出
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 scroll-reveal">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">最新動態</h2>
                <p class="text-gray-600 dark:text-gray-300">實時關注社區最新發展</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- 最近交易 -->
                <div class="card scroll-reveal p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">最近交易</h3>
                        <a href="{{ route('points.transparency') }}" class="text-blue-600 dark:text-blue-400 text-sm hover:underline" data-sound="nav-click">
                            查看全部
                        </a>
                    </div>
                    <div class="space-y-4">
                        @forelse($recentActivity['transactions']->take(5) as $transaction)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $transaction->user->name ?? '匿名用戶' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $transaction->type }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium {{ $transaction->amount > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount, 8) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $transaction->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 dark:text-gray-400">暫無交易記錄</p>
                        @endforelse
                    </div>
                </div>

                <!-- 最近提案 -->
                <div class="card scroll-reveal p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">最近提案</h3>
                        <a href="{{ route('ecosystem.governance') }}" class="text-blue-600 dark:text-blue-400 text-sm hover:underline" data-sound="nav-click">
                            查看全部
                        </a>
                    </div>
                    <div class="space-y-4">
                        @forelse($recentActivity['proposals'] as $proposal)
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">{{ $proposal->title }}</h4>
                                    <span class="px-2 py-1 text-xs rounded-full {{
                                        $proposal->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                        ($proposal->status === 'draft' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                        'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200')
                                    }}">
                                        {{ $proposal->status }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $proposal->creator->name ?? '匿名' }} • {{ $proposal->created_at->diffForHumans() }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">{{ Str::limit($proposal->description, 100) }}</p>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 dark:text-gray-400">暫無提案</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // 實時更新統計數據
    function updateEcosystemStats() {
        fetch('{{ route("ecosystem.api.stats") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 更新統計數字，這裡可以添加動畫效果
                    console.log('Stats updated:', data.data);
                }
            })
            .catch(error => console.log('Failed to update stats:', error));
    }

    // 每2分鐘更新一次統計數據
    setInterval(updateEcosystemStats, 120000);

    // 頁面加載完成後的動畫
    document.addEventListener('DOMContentLoaded', function() {
        // 添加滾動顯示動畫
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');

                    // 為統計卡片添加特殊動畫
                    if (entry.target.querySelector('h3')) {
                        const number = entry.target.querySelector('h3');
                        const finalValue = parseInt(number.textContent.replace(/,/g, ''));
                        animateCounter(number, 0, finalValue, 2000);
                    }
                }
            });
        }, observerOptions);

        document.querySelectorAll('.scroll-reveal').forEach(el => {
            observer.observe(el);
        });
    });

    // 數字動畫函數
    function animateCounter(element, start, end, duration) {
        const startTime = performance.now();

        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const current = Math.floor(start + (end - start) * easeOutQuart(progress));
            element.textContent = current.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        }

        requestAnimationFrame(updateCounter);
    }

    // 緩動函數
    function easeOutQuart(t) {
        return 1 - (--t) * t * t * t;
    }
</script>
@endpush