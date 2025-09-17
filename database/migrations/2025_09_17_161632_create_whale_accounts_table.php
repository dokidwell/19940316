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
        Schema::create('whale_accounts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->unique()->comment('用户ID');
            $table->string('alipay_user_id')->unique()->comment('支付宝用户ID');
            $table->string('whale_user_id')->comment('鯨探用户ID');
            $table->string('nickname')->nullable()->comment('鯨探昵称');
            $table->string('avatar')->nullable()->comment('鯨探头像');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->comment('绑定状态');
            $table->timestamp('bound_at')->comment('绑定时间');
            $table->timestamp('last_sync_at')->nullable()->comment('最后同步时间');
            $table->json('sync_data')->nullable()->comment('同步数据JSON');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['alipay_user_id']);
            $table->index(['whale_user_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whale_accounts');
    }
};
