<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->integer('completed_count')->default(0)->comment('完成次数');
            $table->datetime('last_completed_at')->nullable()->comment('最后完成时间');
            $table->datetime('reset_at')->nullable()->comment('重置时间');
            $table->json('progress_data')->nullable()->comment('进度数据JSON');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'expired'])->default('pending');
            $table->timestamps();

            $table->unique(['user_id', 'task_id']);
            $table->index(['user_id', 'status']);
            $table->index('last_completed_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_tasks');
    }
};