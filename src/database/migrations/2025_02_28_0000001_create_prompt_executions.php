<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('prompt_executions', function (Blueprint $table) {
            $table->id();
            $table->string('prompt_name');
            $table->unsignedInteger('prompt_version');
            $table->json('input')->nullable();
            $table->text('output')->nullable();
            $table->unsignedInteger('tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->decimal('cost', 8, 6)->nullable();
            $table->string('model')->nullable();
            $table->string('provider')->nullable();
            $table->json('feedback')->nullable();
            $table->timestamps();

            $table->index(['prompt_name', 'prompt_version', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('prompt_executions');
    }
};
