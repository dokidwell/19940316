<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('consumption_scenario_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_paid', 16, 8)->comment('支付积分');
            $table->datetime('purchased_at')->comment('购买时间');
            $table->datetime('expires_at')->nullable()->comment('过期时间');
            $table->enum('status', ['active', 'expired', 'used', 'cancelled'])->default('active');
            $table->json('purchase_data')->nullable()->comment('购买数据JSON');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['consumption_scenario_id', 'purchased_at']);
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_consumptions');
    }
};