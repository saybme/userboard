<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteSaybmeUbUbformvalues extends Migration
{
    public function up()
    {
        Schema::dropIfExists('saybme_ub_ubformvalues');
    }
    
    public function down()
    {
        Schema::create('saybme_ub_ubformvalues', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
        });
    }
}
