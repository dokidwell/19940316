@extends('layouts.app')

@section('title', '註冊 - HOHO')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-lg">H</span>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                註冊 HOHO 帳戶
            </h2>
        </div>
        <div class="mt-8 space-y-6">
            <p class="text-center text-gray-600 dark:text-gray-400">
                註冊功能正在開發中...
            </p>
            <div class="text-center">
                <a href="{{ route('artworks.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    返回首頁
                </a>
            </div>
        </div>
    </div>
</div>
@endsection