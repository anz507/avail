<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAVCAvailCalendarTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avail_calendars', function(Blueprint $table)
        {
            $table->bigIncrements('calendar_id');
            $table->string('calendar_name', 255);
            $table->string('status', 15);
            $table->timestamps();

            $table->index(array('status', 'calendar_id'), 'status_calendaid_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('avail_calendars');
    }

}
