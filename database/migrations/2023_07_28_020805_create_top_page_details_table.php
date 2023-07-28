<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTopPageDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('top_page_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('top_page_id');
            $table->string('name');
            $table->string('description');
            $table->string('content');
            $table->string('lang')->default('en');
            $table->foreign('top_page_id')->references('id')->on('top_pages')->onDelete('cascade');
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
        Schema::dropIfExists('top_page_details');
    }
}
