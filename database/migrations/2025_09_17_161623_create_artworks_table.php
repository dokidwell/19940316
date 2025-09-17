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
        Schema::create('artworks', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('作品标题');
            $table->text('description')->nullable()->comment('作品描述');
            $table->enum('type', ['image', 'long_story', 'glb_model', 'sequence_animation', 'video'])->comment('作品类型');
            $table->enum('source', ['official', 'user'])->default('user')->comment('作品来源');
            $table->enum('status', ['pending', 'approved', 'rejected', 'draft'])->default('pending')->comment('审核状态');
            $table->json('files')->comment('文件信息JSON');
            $table->string('thumbnail')->nullable()->comment('缩略图URL');
            $table->json('metadata')->nullable()->comment('元数据(尺寸、时长等)');
            $table->bigInteger('user_id')->unsigned()->comment('创作者ID');
            $table->string('artist_name')->nullable()->comment('艺术家名称');
            $table->string('artist_contact')->nullable()->comment('艺术家联系方式');
            $table->integer('views_count')->default(0)->comment('观看次数');
            $table->integer('likes_count')->default(0)->comment('点赞次数');
            $table->integer('comments_count')->default(0)->comment('评论次数');
            $table->integer('sort_order')->default(0)->comment('排序权重');
            $table->boolean('is_featured')->default(false)->comment('是否精选');
            $table->boolean('is_nft')->default(false)->comment('是否已铸造NFT');
            $table->string('nft_token_id')->nullable()->comment('NFT TokenID');
            $table->decimal('price', 10, 2)->nullable()->comment('售价');
            $table->enum('privacy', ['public', 'unlisted', 'private'])->default('public')->comment('隐私设置');
            $table->json('tags')->nullable()->comment('标签JSON数组');
            $table->timestamp('published_at')->nullable()->comment('发布时间');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['status', 'source']);
            $table->index(['is_featured', 'sort_order']);
            $table->index(['type']);
            $table->index(['published_at']);
            $table->index(['views_count']);
            $table->index(['likes_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artworks');
    }
};
