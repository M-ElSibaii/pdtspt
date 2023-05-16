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
        Schema::table('depreciatedgops', function (Blueprint $table) {
            $table->string('gopGUID', 255);
            $table->string('pdtGUID', 255);
            $table->date('depreciationDate');
            $table->text('depreciationExplanation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('depreciatedgops', function (Blueprint $table) {
            //
        });
    }
};
