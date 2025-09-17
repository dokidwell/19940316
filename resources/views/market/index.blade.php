@extends('layouts.app')

@section('title', '數位市場 - HOHO')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12 fade-in-up">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                <span class="gradient-text">數位藝術市場</span>
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                發現、購買和出售獨特的數位藝術作品和收藏品
            </p>
        </div>

        <!-- Filter Bar -->
        <div class="card p-6 mb-8 fade-in-up" style="animation-delay: 0.1s;">
            <div class="flex flex-col lg:flex-row gap-4 items-center">
                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <select class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                        <option>所有類型</option>
                        <option>數位繪畫</option>
                        <option>3D 模型</option>
                        <option>動畫作品</option>
                        <option>收藏卡片</option>
                    </select>

                    <select class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                        <option>價格排序</option>
                        <option>價格：低到高</option>
                        <option>價格：高到低</option>
                        <option>最新上架</option>
                        <option>最受歡迎</option>
                    </select>

                    <div class="flex gap-2">
                        <input type="number" placeholder="最低價格" class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                        <input type="number" placeholder="最高價格" class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                    </div>
                </div>

                <button class="btn-primary shrink-0">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    搜尋
                </button>
            </div>
        </div>

        <!-- Featured Items -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 scroll-reveal">精選作品</h2>
            <div class="artwork-grid">
                @for($i = 1; $i <= 8; $i++)
                <div class="card card-hover scroll-reveal" style="animation-delay: {{ $i * 0.1 }}s;">
                    <div class="relative">
                        <div class="w-full h-64 bg-gradient-to-br from-purple-400 to-pink-600 rounded-t-2xl flex items-center justify-center">
                            <svg class="w-16 h-16 text-white opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <!-- Rarity Badge -->
                        <div class="absolute top-3 left-3">
                            <span class="px-2 py-1 bg-yellow-500 text-white text-xs font-bold rounded-full">
                                {{ ['Common', 'Rare', 'Epic', 'Legendary'][rand(0, 3)] }}
                            </span>
                        </div>
                        <!-- Favorite Button -->
                        <button class="absolute top-3 right-3 p-2 bg-white/80 dark:bg-gray-800/80 rounded-full backdrop-blur-sm hover:bg-white dark:hover:bg-gray-800 transition-colors duration-200">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">市場作品 #{{ str_pad($i, 4, '0', STR_PAD_LEFT) }}</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">獨特的數位藝術收藏品</p>

                        <!-- Creator Info -->
                        <div class="flex items-center mb-4">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mr-3">
                                <span class="text-white text-sm font-bold">A</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">藝術家名稱</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">@artist{{ $i }}</p>
                            </div>
                        </div>

                        <!-- Price and Action -->
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">價格</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ rand(100, 999) }} HOHO</p>
                            </div>
                            <button class="btn-primary text-sm px-4 py-2">
                                購買
                            </button>
                        </div>
                    </div>
                </div>
                @endfor
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
            <div class="card text-center p-6 scroll-reveal">
                <div class="text-3xl font-bold gradient-text mb-2">12,543</div>
                <p class="text-gray-600 dark:text-gray-300">總作品數</p>
            </div>
            <div class="card text-center p-6 scroll-reveal" style="animation-delay: 0.1s;">
                <div class="text-3xl font-bold gradient-text mb-2">3,247</div>
                <p class="text-gray-600 dark:text-gray-300">活躍創作者</p>
            </div>
            <div class="card text-center p-6 scroll-reveal" style="animation-delay: 0.2s;">
                <div class="text-3xl font-bold gradient-text mb-2">847K</div>
                <p class="text-gray-600 dark:text-gray-300">總交易額</p>
            </div>
            <div class="card text-center p-6 scroll-reveal" style="animation-delay: 0.3s;">
                <div class="text-3xl font-bold gradient-text mb-2">98.5%</div>
                <p class="text-gray-600 dark:text-gray-300">用戶滿意度</p>
            </div>
        </div>

        <!-- Top Creators -->
        <div class="card p-8 scroll-reveal">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">熱門創作者</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @for($i = 1; $i <= 3; $i++)
                <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mr-4">
                        <span class="text-white font-bold">{{ chr(64 + $i) }}</span>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 dark:text-white">創作者 {{ $i }}</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ rand(50, 200) }} 作品</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ rand(10, 99) }}K HOHO</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">總銷售額</p>
                    </div>
                </div>
                @endfor
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Market specific interactions
document.addEventListener('DOMContentLoaded', function() {
    // Favorite buttons
    const favoriteButtons = document.querySelectorAll('button svg[stroke]');
    favoriteButtons.forEach(button => {
        button.parentElement.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.toggle('text-red-500');
            // Add API call to toggle favorite
        });
    });

    // Scroll reveal
    function revealOnScroll() {
        const reveals = document.querySelectorAll('.scroll-reveal');
        reveals.forEach(reveal => {
            const windowHeight = window.innerHeight;
            const revealTop = reveal.getBoundingClientRect().top;
            const revealPoint = 150;

            if (revealTop < windowHeight - revealPoint) {
                reveal.classList.add('active');
            }
        });
    }

    window.addEventListener('scroll', revealOnScroll);
    window.addEventListener('load', revealOnScroll);
});
</script>
@endpush
@endsection