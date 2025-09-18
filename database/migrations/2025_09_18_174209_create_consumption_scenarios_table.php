<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('consumption_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('scenario_key')->unique()->comment('消费场景键名');
            $table->string('name')->comment('场景名称');
            $table->text('description')->nullable()->comment('场景描述');
            $table->string('category')->comment('场景分类: governance, premium, promotion, utility');
            $table->decimal('price', 16, 8)->comment('消费价格');
            $table->integer('duration_hours')->nullable()->comment('效果持续时间(小时)');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('daily_limit')->nullable()->comment('每日购买限制');
            $table->integer('total_limit')->nullable()->comment('总购买限制');
            $table->json('requirements')->nullable()->comment('购买要求JSON');
            $table->json('effects')->nullable()->comment('效果配置JSON');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index('sort_order');
        });
    }

    public function down()
    {
        Schema::dropIfExists('consumption_scenarios');
    }
};