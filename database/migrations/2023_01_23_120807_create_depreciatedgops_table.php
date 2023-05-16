<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('depreciatedgops', function (Blueprint $table) {
            $table->id();
            $table->string('gopGUID', 255);
            $table->string('pdtGUID', 255);
            $table->date('depreciationDate');
            $table->text('depreciationExplanation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('depreciatedgops');
    }
};
