<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDatabaseStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Drop depreciated tables
        Schema::dropIfExists('depreciatedgops');
        Schema::dropIfExists('depreciatedpdts');
        Schema::dropIfExists('depreciatedproperties');
        Schema::dropIfExists('likes');

        // 2. Add constructionobject table
        Schema::create('constructionobjects', function (Blueprint $table) {
            $table->string('GUID', 255);
            $table->string('referenceDocumentGUID', 255)->nullable();
            $table->string('constructionObjectNameEn', 255);
            $table->string('constructionObjectNamePt', 255);
            $table->date('dateOfRevision');
            $table->date('dateOfVersion');
            $table->date('updated_at')->default(now());;
            $table->date('created_at')->default(now());;
            $table->string('status')->nullable();
            $table->integer('versionNumber');
            $table->integer('revisionNumber');
            $table->text('descriptionEn')->nullable();
            $table->text('descriptionPt')->nullable();
            $table->primary('GUID');
            $table->foreign('referenceDocumentGUID')->references('GUID')->on('referencedocuments');
        });

        // 3. Add constructionObjectGUID to productdatatemplates
        Schema::table('productdatatemplates', function (Blueprint $table) {
            $table->integer('editionNumber')->nullable();
            $table->date('dateOfEdition')->nullable();
            $table->string('constructionObjectGUID', 255)->nullable();
            $table->foreign('constructionObjectGUID')->references('GUID')->on('constructionobjects');
        });

        // 4. Add propertyId to properties
        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedInteger('propertyId')->nullable();
            $table->foreign('propertyId')->references('Id')->on('propertiesdatadictionaries');
        });

        // 5. Add depreciation columns to groupofproperties and propertiesdatadictionaries
        Schema::table('groupofproperties', function (Blueprint $table) {
            $table->text('depreciationExplanation')->nullable();
            $table->date('depreciationDate')->nullable();
        });

        Schema::table('propertiesdatadictionaries', function (Blueprint $table) {
            $table->text('depreciationExplanation')->nullable();
            $table->date('depreciationDate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        //  Remove propertyId from properties
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['propertyId']);
            $table->dropColumn('propertyId');
        });

        //  Remove constructionObjectGUID from productdatatemplates
        Schema::table('productdatatemplates', function (Blueprint $table) {
            $table->dropForeign(['constructionObjectGUID']);
            $table->dropColumn('constructionObjectGUID');
            $table->dropColumn('editionNumber');
        });

        //  Drop constructionobject table
        Schema::dropIfExists('constructionobjects');
    }
}
