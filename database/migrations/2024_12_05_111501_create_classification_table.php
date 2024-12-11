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
        Schema::create('classification', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('classificationSystem'); // The classification system name
            $table->unsignedBigInteger('projectId'); // Foreign key for the project
            $table->timestamps(); // Created at and updated at

            // Define the foreign key constraint
            $table->foreign('projectId')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('classification');
    }
};
