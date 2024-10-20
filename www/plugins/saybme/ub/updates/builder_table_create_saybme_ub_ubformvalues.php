<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbUbformvalues extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_ubformvalues', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('saybme_ub_ubformvalues');
    }
}
