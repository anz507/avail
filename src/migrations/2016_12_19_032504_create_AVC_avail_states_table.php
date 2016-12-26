<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAVCAvailStatesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('avail_states', function(Blueprint $table)
		{
			$table->bigIncrements('state_id');
			$table->string('state', 100);
			$table->tinyInteger('state_order')->nullable()->default(0);
			$table->timestamps();

			$table->index('state', 'state_idx');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('avail_states');
	}

}
