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
        Schema::create('groupofproperties', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('GUID', 255);
            $table->unsignedInteger('pdtId');
            $table->string('referenceDocumentGUID', 255)->nullable();
            $table->string('gopNameEn', 255);
            $table->string('gopNamePt', 255);
            $table->text('definitionEn')->nullable();
            $table->text('definitionPt')->nullable();
            $table->text('status')->nullable();
            $table->date('dateOfCreation')->nullable();
            $table->date('dateofActivation')->nullable();
            $table->date('dateOfLastChange')->nullable();
            $table->date('dateOfRevision');
            $table->date('dateOfVersion');
            $table->integer('versionNumber');
            $table->integer('revisionNumber');
            $table->text('listOfReplacedProperties')->nullable();
            $table->text('listOfReplacingProperties')->nullable();
            $table->text('relationToOtherDataDictionaries')->nullable();
            $table->string('creatorsLanguage', 255)->nullable();
            $table->text('visualRepresentation')->nullable();
            $table->text('countryOfUse')->nullable();
            $table->text('countryOfOrigin')->nullable();
            $table->text('categoryOfGroupOfProperties')->nullable();
            $table->text('parentGroupOfProperties')->nullable();
            $table->date('updated_at')->default(now());;
            $table->date('created_at')->default(now());;
            $table->index('pdtId');
            $table->foreign('pdtId')->references('Id')->on('productdatatemplates');
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
        Schema::dropIfExists('groupofproperties');
    }
};
