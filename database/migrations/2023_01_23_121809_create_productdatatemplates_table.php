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
        Schema::create('productdatatemplates', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('GUID', 255);
            $table->string('referenceDocumentGUID', 255)->nullable();
            $table->string('pdtNameEn', 255);
            $table->string('pdtNamePt', 255);
            $table->date('dateOfRevision');
            $table->date('dateOfVersion');
            $table->date('updated_at');
            $table->date('created_at');
            $table->string('status', 45)->nullable();;
            $table->string('versionNumber', 255);
            $table->string('revisionNumber', 255);
            $table->text('descriptionEn')->nullable();;
            $table->text('descriptionPt')->nullable();;
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
        Schema::dropIfExists('productdatatemplates');
    }
};
