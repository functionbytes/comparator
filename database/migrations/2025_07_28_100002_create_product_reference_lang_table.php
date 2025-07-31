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
        Schema::create('product_reference_lang', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('url', 2048)->nullable();
            $table->unsignedBigInteger('lang_id')->nullable();
            $table->text('characteristics')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('price', 12, 2)->default(0.00);
            $table->decimal('reduction', 20, 6)->default(0);
            $table->tinyInteger('available')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('reference_id')->references('id')->on('product_reference')->onDelete('cascade');
            $table->foreign('lang_id')->references('id')->on('langs')->onDelete('cascade');
            $table->unique(['reference_id', 'id', 'lang_id'], 'product_reference_lang_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reference_lang');
    }
};
