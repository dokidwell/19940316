<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('任务唯一标识');
            $table->string('name')->comment('任务名称');
            $table->text('description')->nullable()->comment('任务描述');
            $table->string('type')->comment('任务类型: daily, weekly, once, per_action');
            $table->decimal('reward_points', 16, 8)->default(0)->comment('奖励积分');
            $table->integer('max_completions')->default(1)->comment('最大完成次数');
            $table->json('conditions')->nullable()->comment('完成条件JSON');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->datetime('start_at')->nullable()->comment('开始时间');
            $table->datetime('end_at')->nullable()->comment('结束时间');
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('sort_order');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};