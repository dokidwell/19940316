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
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['user_action', 'admin_action', 'system_event', 'point_transaction', 'nft_sync', 'proposal_event'])->comment('日志类型');
            $table->enum('level', ['info', 'warning', 'error', 'critical'])->default('info')->comment('日志级别');
            $table->string('action')->comment('动作名称');
            $table->text('description')->comment('日志描述');
            $table->bigInteger('user_id')->unsigned()->nullable()->comment('用户ID');
            $table->bigInteger('admin_id')->unsigned()->nullable()->comment('管理员ID');
            $table->string('ip_address', 45)->nullable()->comment('IP地址');
            $table->string('user_agent')->nullable()->comment('用户代理');
            $table->string('reference_type')->nullable()->comment('关联对象类型');
            $table->bigInteger('reference_id')->unsigned()->nullable()->comment('关联对象ID');
            $table->json('metadata')->nullable()->comment('相关数据JSON');
            $table->json('old_values')->nullable()->comment('修改前数据');
            $table->json('new_values')->nullable()->comment('修改后数据');
            $table->boolean('is_public')->default(false)->comment('是否在透明公示中显示');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['type', 'level']);
            $table->index(['user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['is_public', 'created_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
