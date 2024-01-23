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
        Schema::create('referencedocuments', function (Blueprint $table) {
            $table->string('GUID', 255);
            $table->text('rdName');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->text('status')->nullable();
            $table->date('updated_at')->default(now());;
            $table->date('created_at')->default(now());;
            $table->primary('GUID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('referencedocuments');
    }
};
