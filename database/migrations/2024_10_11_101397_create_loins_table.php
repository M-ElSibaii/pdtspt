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
        Schema::create('loins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('userId');
            $table->unsignedInteger('projectId');
            $table->string('projectName');
            $table->string('objectName');
            $table->string('actorProviding');
            $table->string('actorRequesting');
            $table->string('pdtName')->nullable();
            $table->string('ifcClass')->nullable();
            $table->string('ifcClassName')->nullable();
            $table->string('ifcClassDescription')->nullable();
            $table->string('ifcClassPredefinedType')->nullable();
            $table->string('milestone');
            $table->string('materialName')->nullable();;
            $table->string('purpose');
            $table->text('detail')->nullable();
            $table->string('dimension')->nullable();
            $table->string('location')->nullable();
            $table->string('appearance')->nullable();
            $table->string('parametricBehaviour')->nullable();
            $table->json('documentation')->nullable();
            $table->json('properties')->nullable();
            $table->string('classificationSystem')->nullable();
            $table->string('classificationTable')->nullable();
            $table->string('classificationCode')->nullable();
            $table->timestamps();
            $table->foreign('projectId')->references('Id')->on('projects');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loins');
    }
};
