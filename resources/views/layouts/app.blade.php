<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'HOHO - 創意藝術平台')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    <!-- Meta for Apple-style UI -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="HOHO">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Preload for better performance -->
    <link rel="preload" href="{{ Vite::asset('resources/css/app.css') }}" as="style">
    <link rel="preload" href="{{ Vite::asset('resources/js/app.js') }}" as="script">

    @stack('styles')

    <!-- Apple-style UI Settings Panel -->
    <div x-data="{ settingsOpen: false }" x-cloak>
        <!-- Settings Toggle -->
        <button @click="settingsOpen = !settingsOpen"
                class="fixed bottom-6 right-6 z-50 w-12 h-12 bg-apple-blue hover:bg-blue-600 text-white rounded-full shadow-apple-lg hover:shadow-apple-xl transition-all duration-250 apple flex items-center justify-center group"
                x-sound="click"
                title="UI Settings">
            <svg class="w-5 h-5 transition-transform duration-300" :class="{ 'rotate-180': settingsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </button>

        <!-- Settings Panel -->
        <div x-show="settingsOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-full"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform translate-x-full"
             class="fixed top-0 right-0 bottom-0 w-80 bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border-l border-gray-200 dark:border-gray-700 z-40 overflow-y-auto scrollbar-thin">

            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">UI 設置</h2>
                    <button @click="settingsOpen = false" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" x-sound="click">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Dark Mode Toggle -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">暗色模式</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">切換明暗主題</p>
                        </div>
                        <button @click="$store.ui.toggleDarkMode()"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="$store.ui.darkMode ? 'bg-apple-blue' : 'bg-gray-200'"
                                x-sound="click">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                  :class="$store.ui.darkMode ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                </div>

                <!-- Sound Toggle -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">音效</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">啟用互動音效</p>
                        </div>
                        <button @click="$store.ui.toggleSound()"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="$store.ui.soundEnabled ? 'bg-apple-blue' : 'bg-gray-200'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                  :class="$store.ui.soundEnabled ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                </div>

                <!-- Animation Toggle -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">動畫</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">啟用過渡動畫</p>
                        </div>
                        <button @click="$store.ui.toggleAnimations()"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="$store.ui.animationsEnabled ? 'bg-apple-blue' : 'bg-gray-200'"
                                x-sound="click">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                  :class="$store.ui.animationsEnabled ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="space-y-3">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">快速操作</h3>

                    <button @click="hoho.showToast('這是一個測試通知', 'success')"
                            class="btn btn-ghost w-full justify-start"
                            x-sound="click">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM5 12V7a5 5 0 015-5h4a5 5 0 015 5v4.38l-2 2.62H10a5 5 0 01-5-5z"></path>
                        </svg>
                        測試通知
                    </button>

                    <button @click="hoho.showModal('<p>這是一個演示模態框</p>', { header: '演示' })"
                            class="btn btn-ghost w-full justify-start"
                            x-sound="click">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        測試模態框
                    </button>
                </div>

                <!-- Version Info -->
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                        🍎 Apple-Style UI v2.0<br>
                        由 HOHO 平台驅動
                    </p>
                </div>
            </div>
        </div>

        <!-- Settings Overlay -->
        <div x-show="settingsOpen"
             @click="settingsOpen = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/20 backdrop-blur-sm z-30"></div>
    </div>
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-900 transition-colors duration-300 apple" x-data :class="{ 'dark': $store.ui.darkMode }">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass dark:glass-dark border-b border-gray-200/20 dark:border-gray-700/20 transition-all duration-300 apple" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('artworks.index') }}" class="flex items-center space-x-2 group hover-grow focus-ring" x-sound="click">
                        <div class="w-8 h-8 gradient-bg-apple rounded-xl flex items-center justify-center transition-all duration-250 apple group-hover:shadow-glow">
                            <span class="text-white font-bold text-sm drop-shadow-sm">H</span>
                        </div>
                        <span class="text-xl font-bold gradient-text">HOHO</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-0.5">
                    <a href="{{ route('artworks.index') }}" class="nav-link @if(request()->routeIs('artworks.*')) nav-link-active @endif" x-sound="click">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        作品
                    </a>
                    <a href="{{ route('create.index') }}" class="nav-link @if(request()->routeIs('create.*')) nav-link-active @endif" x-sound="click">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        創作
                    </a>
                    <a href="{{ route('market.index') }}" class="nav-link @if(request()->routeIs('market.*')) nav-link-active @endif" x-sound="click">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        集市
                    </a>
                    <a href="{{ route('ecosystem.index') }}" class="nav-link @if(request()->routeIs('ecosystem.*')) nav-link-active @endif" x-sound="click">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        生態
                    </a>
                </div>

                <!-- Right Side - Balance & Avatar -->
                <div class="hidden md:flex items-center space-x-4">
                    <!-- Balance Display -->
                    <div class="flex items-center space-x-2 px-4 py-2 glass dark:glass-dark rounded-xl border border-gray-200/50 dark:border-gray-700/50 hover-glow">
                        <div class="relative">
                            <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        </div>
                        <span class="text-sm font-mono font-medium text-gray-900 dark:text-white" id="balance-display">
                            {{ Auth::check() ? number_format(Auth::user()->points_balance, 8) : '0.00000000' }}
                        </span>
                    </div>

                    <!-- Avatar Dropdown -->
                    <div class="dropdown" x-data="dropdown()">
                        <button @click="toggle()" class="flex items-center space-x-2 p-1 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-250 apple hover-grow focus-ring" x-sound="click">
                            @auth
                                <img src="{{ Auth::user()->avatar_url ?? '/default-avatar.png' }}" alt="Avatar" class="w-8 h-8 rounded-lg object-cover border-2 border-gray-200 dark:border-gray-600">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ Auth::user()->name }}</span>
                            @else
                                <div class="w-8 h-8 bg-gradient-to-br from-gray-400 to-gray-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">訪客</span>
                            @endauth
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-250 apple" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open" @click.away="close()" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 -translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 -translate-y-2" class="dropdown-content w-64 py-2">
                            @auth
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center space-x-3">
                                        <img src="{{ Auth::user()->avatar_url ?? '/default-avatar.png' }}" alt="Avatar" class="w-10 h-10 rounded-xl object-cover">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ Auth::user()->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->hoho_id }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="py-1">
                                    <a href="{{ route('profile.index') }}" class="dropdown-link" x-sound="click">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        個人資料
                                    </a>
                                    <a href="{{ route('points.wallet') }}" class="dropdown-link" x-sound="click">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        我的錢包
                                    </a>
                                    <a href="{{ route('whale.index') }}" class="dropdown-link" x-sound="click">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        鯨探集成
                                    </a>
                                    <a href="{{ route('profile.settings') }}" class="dropdown-link" x-sound="click">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        設置
                                    </a>
                                </div>
                                <div class="border-t border-gray-100 dark:border-gray-700 py-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-link w-full text-left text-red-600 dark:text-red-400" x-sound="click">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                            登出
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="py-1">
                                    <a href="{{ route('login') }}" class="dropdown-link" x-sound="click">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                        </svg>
                                        登入
                                    </a>
                                    <a href="{{ route('register') }}" class="dropdown-link" x-sound="click">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                        註冊
                                    </a>
                                </div>
                            @endauth
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="btn btn-ghost p-2" x-sound="click">
                        <svg class="w-6 h-6 transition-transform duration-300" :class="{ 'rotate-90': mobileMenuOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mobileMenuOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-full"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-full"
             class="md:hidden glass-strong dark:glass-dark-strong border-t border-gray-200/20 dark:border-gray-700/20">
            <div class="px-2 pt-2 pb-3 space-y-1 stagger-children">
                <a href="{{ route('artworks.index') }}" class="mobile-nav-link @if(request()->routeIs('artworks.*')) mobile-nav-link-active @endif" x-sound="click">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    作品
                </a>
                <a href="{{ route('create.index') }}" class="mobile-nav-link @if(request()->routeIs('create.*')) mobile-nav-link-active @endif" x-sound="click">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    創作
                </a>
                <a href="{{ route('market.index') }}" class="mobile-nav-link @if(request()->routeIs('market.*')) mobile-nav-link-active @endif" x-sound="click">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    集市
                </a>
                <a href="{{ route('ecosystem.index') }}" class="mobile-nav-link @if(request()->routeIs('ecosystem.*')) mobile-nav-link-active @endif" x-sound="click">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    生態
                </a>

                <!-- Mobile User Section -->
                @auth
                    <div class="border-t border-gray-200 dark:border-gray-700 my-2 pt-2">
                        <div class="flex items-center px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                            <span>餘額: {{ number_format(Auth::user()->points_balance, 8) }}</span>
                        </div>
                        <a href="{{ route('profile.index') }}" class="mobile-nav-link" x-sound="click">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            個人資料
                        </a>
                        <a href="{{ route('points.wallet') }}" class="mobile-nav-link" x-sound="click">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            我的錢包
                        </a>
                        <a href="{{ route('whale.index') }}" class="mobile-nav-link" x-sound="click">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            鯨探集成
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="mobile-nav-link w-full text-left text-red-600 dark:text-red-400" x-sound="click">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                登出
                            </button>
                        </form>
                    </div>
                @else
                    <div class="border-t border-gray-200 dark:border-gray-700 my-2 pt-2">
                        <a href="{{ route('login') }}" class="mobile-nav-link" x-sound="click">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            登入
                        </a>
                        <a href="{{ route('register') }}" class="mobile-nav-link" x-sound="click">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            註冊
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-16 min-h-screen">
        <!-- Page Loading Indicator -->
        <div id="page-loading" class="fixed top-16 left-0 right-0 h-1 bg-gradient-to-r from-apple-blue to-purple-600 transform scale-x-0 origin-left transition-transform duration-500 z-40"></div>

        @yield('content')
    </main>

    <!-- Global Toast Container -->
    <div id="toast-container" class="fixed top-20 right-4 z-50 space-y-2 pointer-events-none">
        <!-- Toasts will be inserted here -->
    </div>

    @stack('scripts')

    <!-- Initialize Alpine.js x-cloak -->
    <style>
        [x-cloak] { display: none !important; }

        /* Page transition styles */
        .page-transition {
            transition: all 0.3s cubic-bezier(0.23, 1, 0.320, 1);
        }

        .page-transition.leaving {
            opacity: 0.7;
            transform: scale(0.98) translateY(10px);
        }

        /* Loading bar animation */
        .loading-bar {
            animation: loading-progress 2s ease-in-out infinite;
        }

        @keyframes loading-progress {
            0% { transform: scaleX(0); }
            50% { transform: scaleX(0.7); }
            100% { transform: scaleX(1); }
        }

        /* Enhanced focus styles */
        .focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.3);
        }

        /* Skeleton loading improvements */
        .skeleton-enhanced {
            background: linear-gradient(90deg,
                rgba(0, 0, 0, 0.1) 0%,
                rgba(0, 0, 0, 0.15) 50%,
                rgba(0, 0, 0, 0.1) 100%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite ease-in-out;
        }

        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>

    <script>
        // HOHO Apple-style Enhanced Interactions
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🍎 HOHO Apple-Style UI Enhanced - Ready!');

            // Simple balance update for authenticated users
            @auth
            function updateBalance() {
                fetch('/api/user/balance', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const balanceElement = document.getElementById('balance-display');
                        if (balanceElement) {
                            balanceElement.textContent = Number(data.balance).toLocaleString('en-US', {
                                minimumFractionDigits: 8,
                                maximumFractionDigits: 8
                            });
                        }
                    }
                })
                .catch(() => {}); // Silent fail
            }

            // Update balance every 30 seconds
            setTimeout(updateBalance, 2000);
            setInterval(updateBalance, 30000);
            @endauth
        });

        // Global notification function
        window.showNotification = function(message, type = 'info') {
            if (window.hoho && window.hoho.showToast) {
                return window.hoho.showToast(message, type);
            }
            console.log(`${type.toUpperCase()}: ${message}`);
        };

        // Real-time balance updates
        function updateBalance() {
            fetch('/api/user/balance')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const balanceElement = document.getElementById('balance-display');
                        if (balanceElement) {
                            const oldBalance = parseFloat(balanceElement.textContent.replace(/,/g, ''));
                            const newBalance = parseFloat(data.balance);

                            if (oldBalance !== newBalance) {
                                balanceElement.textContent = Number(newBalance).toLocaleString('en-US', {
                                    minimumFractionDigits: 8,
                                    maximumFractionDigits: 8
                                });

                                // Add visual feedback for balance change
                                balanceElement.classList.add('animate-bounce-in');
                                soundSystem.playSound(newBalance > oldBalance ? 'success' : 'notification');

                                setTimeout(() => {
                                    balanceElement.classList.remove('animate-bounce-in');
                                }, 600);
                            }
                        }
                    }
                })
                .catch(error => console.log('Balance update failed:', error));
        }

        // Update balance every 30 seconds if user is authenticated
        @auth
        setInterval(updateBalance, 30000);
        @endauth

        // Smooth scroll enhancement
        document.addEventListener('DOMContentLoaded', function() {
            // Add scroll reveal animation to elements
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const scrollObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.scroll-reveal').forEach(el => {
                scrollObserver.observe(el);
            });

            // Enhanced loading states
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>處理中...';
                        soundSystem.playSound('nav-click');
                    }
                });
            });

            // Add premium loading animation to page transitions
            const links = document.querySelectorAll('a[href]:not([href^="#"]):not([href^="mailto:"]):not([href^="tel:"])');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!e.metaKey && !e.ctrlKey && !e.shiftKey) {
                        setTimeout(() => {
                            document.body.style.opacity = '0.7';
                            document.body.style.transform = 'scale(0.98)';
                            document.body.style.transition = 'all 0.3s cubic-bezier(0.23, 1, 0.320, 1)';
                        }, 50);
                    }
                });
            });
        });

        // Notification system
        window.showNotification = function(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 z-[60] p-4 rounded-xl shadow-lg max-w-sm animate-bounce-in ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                type === 'warning' ? 'bg-yellow-500 text-black' :
                'bg-blue-500 text-white'
            }`;

            notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        ${type === 'success' ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>' :
                          type === 'error' ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>' :
                          '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>'}
                    </svg>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);
            soundSystem.playSound(type === 'error' ? 'error' : 'notification');

            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        };
    </script>
</body>
</html>