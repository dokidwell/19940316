<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('economic_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_key')->unique()->comment('配置键名');
            $table->string('config_name')->comment('配置名称');
            $table->text('description')->nullable()->comment('配置描述');
            $table->string('config_type')->comment('配置类型: task_reward, consumption_price, system_parameter');
            $table->decimal('config_value', 16, 8)->comment('配置值');
            $table->decimal('min_value', 16, 8)->default(0)->comment('最小值');
            $table->decimal('max_value', 16, 8)->default(999.99999999)->comment('最大值');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->datetime('effective_at')->nullable()->comment('生效时间');
            $table->foreignId('updated_by')->nullable()->constrained('users')->comment('修改者');
            $table->text('change_reason')->nullable()->comment('修改原因');
            $table->timestamps();

            $table->index(['config_type', 'is_active']);
            $table->index('effective_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('economic_configs');
    }
};