<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAVCAvailBookingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('avail_bookings', function(Blueprint $table)
		{
			$table->bigIncrements('booking_id');
			$table->bigInteger('calendar_id');
			$table->date('calendar_date');
			$table->bigInteger('state_id');
			// external_booking_ids could be integer or string depending on external id data type
			// assuming string as data type for good measure
			$table->string('external_booking_id', 255)->nullable();
			$table->timestamps();

			$table->index(array('calendar_id', 'state_id', 'external_booking_id'), 'calendarid_stateid_externalid_idx');
			$table->index(array('calendar_id', 'calendar_date'), 'calendarid_calendardate_idx');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('avail_bookings');
	}

}
