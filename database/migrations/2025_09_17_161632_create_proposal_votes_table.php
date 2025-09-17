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
        Schema::create('proposal_votes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('proposal_id')->unsigned()->comment('提案ID');
            $table->bigInteger('user_id')->unsigned()->comment('投票用户ID');
            $table->enum('vote', ['support', 'oppose', 'abstain'])->comment('投票选项');
            $table->decimal('points_used', 15, 6)->comment('消耗积分数量');
            $table->text('reason')->nullable()->comment('投票理由');
            $table->timestamps();

            $table->foreign('proposal_id')->references('id')->on('proposals')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unique(['proposal_id', 'user_id'], 'unique_user_vote_per_proposal');
            $table->index(['proposal_id', 'vote']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_votes');
    }
};
