<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('横幅标题');
            $table->text('description')->nullable()->comment('横幅描述');
            $table->enum('type', ['image', 'video', 'animated'])->default('image')->comment('横幅类型');
            $table->string('file_url')->comment('文件URL');
            $table->string('thumbnail_url')->nullable()->comment('缩略图URL');
            $table->string('link_url')->nullable()->comment('链接URL');
            $table->enum('target', ['_self', '_blank'])->default('_self')->comment('链接打开方式');
            $table->enum('position', ['home_hero', 'home_featured', 'sidebar', 'footer'])->default('home_hero')->comment('显示位置');
            $table->boolean('is_active')->default(true)->comment('是否激活');
            $table->integer('sort_order')->default(0)->comment('排序权重');
            $table->integer('click_count')->default(0)->comment('点击次数');
            $table->integer('view_count')->default(0)->comment('展示次数');
            $table->timestamp('starts_at')->nullable()->comment('开始显示时间');
            $table->timestamp('ends_at')->nullable()->comment('结束显示时间');
            $table->bigInteger('created_by')->unsigned()->comment('创建者ID');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['is_active', 'position', 'sort_order']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
