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
        Schema::create('nft_collections', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->comment('用户ID');
            $table->string('token_id')->comment('NFT Token ID');
            $table->string('collection_name')->comment('合集名称');
            $table->string('nft_name')->comment('NFT名称');
            $table->text('description')->nullable()->comment('NFT描述');
            $table->string('image_url')->nullable()->comment('图像URL');
            $table->string('thumbnail_url')->nullable()->comment('缩略图URL');
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common')->comment('稀有度');
            $table->json('attributes')->nullable()->comment('NFT属性JSON');
            $table->json('metadata')->nullable()->comment('元数据JSON');
            $table->enum('status', ['owned', 'transferred', 'burned'])->default('owned')->comment('NFT状态');
            $table->boolean('is_avatar')->default(false)->comment('是否用作头像');
            $table->boolean('is_nickname_source')->default(false)->comment('是否用作昵称来源');
            $table->timestamp('acquired_at')->comment('获得时间');
            $table->timestamp('last_verified_at')->nullable()->comment('最后验证时间');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index(['token_id']);
            $table->index(['collection_name']);
            $table->index(['rarity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nft_collections');
    }
};
