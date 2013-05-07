<?php

use Illuminate\Database\Migrations\Migration;

class CreateSettingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dberry37388_settings', function($table)
		{
			$table->increments('id');
			$table->timestamps();
			$table->string('namespace')->nullable();
			$table->string('group')->nullable();
			$table->text('item')->nullable();
			$table->text('value')->nullable();
			$table->enum('format', array('string', 'json'))->default('string');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExsits('dberry37388_settings');
	}

}
