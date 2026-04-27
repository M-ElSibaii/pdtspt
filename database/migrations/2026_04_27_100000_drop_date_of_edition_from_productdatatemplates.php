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
        Schema::table('productdatatemplates', function (Blueprint $table) {
            $table->dropColumn('dateOfEdition');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productdatatemplates', function (Blueprint $table) {
            $table->date('dateOfEdition')->nullable();
        });
    }
};
