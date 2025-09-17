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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('hoho_id', 10)->unique()->comment('HOHO#唯一ID');
            $table->string('phone', 20)->unique()->nullable()->comment('手机号');
            $table->timestamp('phone_verified_at')->nullable()->comment('手机验证时间');
            $table->string('email')->unique()->nullable()->comment('邮箱');
            $table->timestamp('email_verified_at')->nullable()->comment('邮箱验证时间');
            $table->string('password')->comment('密码');
            $table->string('nickname')->nullable()->comment('昵称(来自NFT)');
            $table->string('avatar_url')->nullable()->comment('头像URL');
            $table->string('avatar_source')->default('default')->comment('头像来源:default,nft,whale');
            $table->enum('status', ['active', 'banned', 'pending'])->default('pending')->comment('用户状态');
            $table->enum('role', ['user', 'admin'])->default('user')->comment('用户角色');
            $table->decimal('points_balance', 15, 6)->default(0)->comment('积分余额');
            $table->timestamp('last_checkin_at')->nullable()->comment('最后签到时间');
            $table->json('notification_settings')->nullable()->comment('通知设置');
            $table->string('referral_code', 8)->unique()->nullable()->comment('推荐码');
            $table->bigInteger('referred_by')->unsigned()->nullable()->comment('推荐人ID');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hoho_id']);
            $table->index(['phone']);
            $table->index(['status']);
            $table->index(['last_checkin_at']);
            $table->foreign('referred_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
