@extends('layouts.app')

@section('title', '創作中心 - HOHO')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12 fade-in-up">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                <span class="gradient-text">開始您的創作</span>
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                上傳您的藝術作品，與社群分享您的創意靈感
            </p>
        </div>

        <!-- Upload Form -->
        <div class="card p-8 fade-in-up" style="animation-delay: 0.2s;">
            <form class="space-y-6">
                <!-- File Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        作品文件
                    </label>
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center hover:border-blue-500 dark:hover:border-blue-400 transition-colors duration-200">
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">拖拽文件到此處或點擊上傳</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">支援 JPG, PNG, GIF, MP4, GLB 格式</p>
                        <button type="button" class="btn-primary mt-4">
                            選擇文件
                        </button>
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2" for="title">
                        作品標題
                    </label>
                    <input type="text" id="title" name="title" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white transition-all duration-200" placeholder="為您的作品命名...">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2" for="description">
                        作品描述
                    </label>
                    <textarea id="description" name="description" rows="4" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white transition-all duration-200" placeholder="描述您的創作靈感和理念..."></textarea>
                </div>

                <!-- Tags -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2" for="tags">
                        標籤
                    </label>
                    <input type="text" id="tags" name="tags" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white transition-all duration-200" placeholder="用逗號分隔標籤，如：抽象,數位藝術,概念">
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2" for="category">
                        作品類型
                    </label>
                    <select id="category" name="category" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:text-white transition-all duration-200">
                        <option value="">選擇作品類型</option>
                        <option value="digital_art">數位繪畫</option>
                        <option value="3d_model">3D 模型</option>
                        <option value="animation">動畫作品</option>
                        <option value="long_story">長圖故事</option>
                    </select>
                </div>

                <!-- Privacy Settings -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-4">
                        隱私設定
                    </label>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="radio" name="privacy" value="public" class="text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600" checked>
                            <span class="ml-3 text-gray-900 dark:text-white">公開 - 所有人都能看到</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="privacy" value="unlisted" class="text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600">
                            <span class="ml-3 text-gray-900 dark:text-white">不公開 - 僅限分享連結</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="privacy" value="private" class="text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600">
                            <span class="ml-3 text-gray-900 dark:text-white">私人 - 僅自己可見</span>
                        </label>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <button type="submit" class="btn-primary flex-1">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        發布作品
                    </button>
                    <button type="button" class="btn-secondary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        儲存草稿
                    </button>
                </div>
            </form>
        </div>

        <!-- Guidelines -->
        <div class="mt-12 card p-6 fade-in-up" style="animation-delay: 0.4s;">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">發布指南</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-300">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>確保作品是您的原創內容</span>
                </div>
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>提供清晰的作品描述</span>
                </div>
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>使用相關的標籤</span>
                </div>
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>遵守社群規範</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// File upload preview
document.addEventListener('DOMContentLoaded', function() {
    // Add drag and drop functionality
    const uploadArea = document.querySelector('.border-dashed');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        uploadArea.classList.add('border-blue-500', 'dark:border-blue-400');
    }

    function unhighlight(e) {
        uploadArea.classList.remove('border-blue-500', 'dark:border-blue-400');
    }

    uploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        // Handle file preview here
        console.log('Files dropped:', files);
    }
});
</script>
@endpush
@endsection