<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRiversTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('rivers', function($table){
			$table->increments('id');
			$table->integer('station_id')->unsigned();
			$table->string('state');
			$table->string('district');
			$table->string('name');
			$table->string('basin');
			$table->timestamp('last_updated');
			$table->decimal('current_level', 12,3);
			$table->decimal('normal_level', 12,3);
			$table->decimal('alert_level', 12,3);
			$table->decimal('warning_level', 12,3);
			$table->decimal('danger_level', 12,3);
			$table->string('status');
			$table->timestamps();
			$table->index('station_id');
			$table->index('state');
			$table->index('current_level');
			$table->index('status');
			$table->index('created_at');
			$table->index('name');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('rivers');
	}

}
