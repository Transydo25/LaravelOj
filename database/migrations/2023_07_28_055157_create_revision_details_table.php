<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRevisionDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('revision_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revision_id');
            $table->string('title');
            $table->string('content');
            $table->string('description');
            $table->string('lang')->default('en');
            $table->foreign('revision_id')->references('id')->on('revisions')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('revision_details');
    }
}
