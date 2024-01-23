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
        Schema::create('properties', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('GUID', 255);
            $table->integer('gopID')->unsigned();
            $table->integer('pdtID')->unsigned();
            $table->string('referenceDocumentGUID', 255)->nullable();
            $table->text('descriptionEn');
            $table->text('descriptionPt');
            $table->text('visualRepresentation')->nullable();
            $table->date('updated_at')->default(now());;
            $table->date('created_at')->default(now());;
            $table->foreign('GUID')->references('GUID')->on('propertiesdatadictionaries');
            $table->foreign('gopID')->references('Id')->on('groupofproperties');
            $table->foreign('pdtID')->references('Id')->on('productdatatemplates');
            $table->foreign('referenceDocumentGUID')->references('GUID')->on('referencedocuments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('properties');
    }
};
