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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('提案标题');
            $table->text('description')->comment('提案描述');
            $table->enum('type', ['governance', 'feature', 'policy', 'economic'])->comment('提案类型');
            $table->enum('status', ['draft', 'voting', 'passed', 'rejected', 'executed'])->default('draft')->comment('提案状态');
            $table->bigInteger('proposer_id')->unsigned()->comment('提案人 ID');
            $table->decimal('support_points', 20, 6)->default(0)->comment('支持积分总数');
            $table->decimal('oppose_points', 20, 6)->default(0)->comment('反对积分总数');
            $table->integer('total_voters')->default(0)->comment('总投票人数');
            $table->decimal('approval_threshold', 5, 2)->default(66.67)->comment('通过阈值(百分比)');
            $table->integer('min_participation')->default(100)->comment('最少参与人数');
            $table->timestamp('voting_starts_at')->nullable()->comment('投票开始时间');
            $table->timestamp('voting_ends_at')->nullable()->comment('投票结束时间');
            $table->timestamp('executed_at')->nullable()->comment('执行时间');
            $table->json('execution_data')->nullable()->comment('执行参数JSON');
            $table->text('rejection_reason')->nullable()->comment('拒绝原因');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('proposer_id')->references('id')->on('users');
            $table->index(['status']);
            $table->index(['voting_starts_at', 'voting_ends_at']);
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
