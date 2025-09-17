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
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->comment('用户ID');
            $table->enum('type', ['earn', 'spend', 'transfer', 'admin_adjust'])->comment('交易类型');
            $table->enum('category', ['checkin', 'vote', 'create', 'whale_bonus', 'referral', 'admin', 'purchase', 'reward'])->comment('交易分类');
            $table->decimal('amount', 15, 6)->comment('积分数量(正负)');
            $table->decimal('balance_before', 15, 6)->comment('交易前余额');
            $table->decimal('balance_after', 15, 6)->comment('交易后余额');
            $table->string('description')->comment('交易说明');
            $table->json('metadata')->nullable()->comment('相关数据JSON');
            $table->string('reference_type')->nullable()->comment('关联对象类型');
            $table->bigInteger('reference_id')->unsigned()->nullable()->comment('关联对象ID');
            $table->bigInteger('admin_id')->unsigned()->nullable()->comment('管理员ID(如果是管理员操作)');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('admin_id')->references('id')->on('users');
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'category']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
