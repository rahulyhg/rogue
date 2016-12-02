<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->integer('event_id')->unsigned()->comment('The event this post corresponds to.');
            $table->foreign('event_id')->references('id')->on('events');

            $table->integer('signup_id')->unsigned()->comment('The signup this Post references.');
            $table->foreign('signup_id')->references('id')->on('signups');

            $table->string('northstar_id')->index()->comment('The user that submitted the thing being reviewed
');
            $table->string('admin_northstar_id')->comment('The NS id of the admin doing the reviewing');

            $table->string('status')->nullable();
            $table->string('old_status')->nullable();
            $table->text('description')->nullable()->comment('A comment left by the reviewer when reviewing.');
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
        Schema::drop('reviews');
    }
}
