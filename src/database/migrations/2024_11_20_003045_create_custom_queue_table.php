<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nino\CustomQueueLaravel\Services\QueueManager;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('custom_queue_tasks', function (Blueprint $table) {
            $table->uuid()->unique()->primary();
            $table->string('class_name');
            $table->string('method');
            $table->json('parameters');
            $table->enum('status', QueueManager::STATUSES)->default(QueueManager::IDLE);
            $table->integer('priority')->default(QueueManager::PRIORITY_NORMAL);
            $table->integer('delay')->default(0);
            $table->integer('retries')->default(0);
            $table->integer('pid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_queue_tasks');
    }
};
