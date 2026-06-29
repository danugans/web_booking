<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('information', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 255);
            $table->text('content');
            $table->string('image', 255)->nullable();
            $table->date('published_at')->nullable();
            $table->timestamps(); // otomatis membuat created_at dan updated_at (nullable by default)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('information');
    }
};
