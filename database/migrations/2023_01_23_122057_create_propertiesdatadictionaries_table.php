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
        Schema::create('propertiesdatadictionaries', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('GUID', 255);
            $table->text('namePt');
            $table->text('nameEn');
            $table->text('definitionPt')->nullable();
            $table->text('definitionEn')->nullable();
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
            $table->text('physicalQuantity')->nullable();
            $table->text('dimension')->nullable();
            $table->text('dataType')->nullable();
            $table->text('dynamicProperty')->nullable();
            $table->text('parametersOfTheDynamicProperty')->nullable();
            $table->text('units')->nullable();
            $table->text('namesOfDefiningValues')->nullable();
            $table->text('definingValues')->nullable();
            $table->text('tolerance')->nullable();
            $table->text('digitalFormat')->nullable();
            $table->text('textFormat')->nullable();
            $table->text('listOfPossibleValuesInLanguageN')->nullable();
            $table->text('boundaryValues')->nullable();
            $table->date('updated_at')->default(now());;
            $table->date('created_at')->default(now());;
            $table->index('GUID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('propertiesdatadictionaries');
    }
};
