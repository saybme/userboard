<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteSaybmeUbFormInputs extends Migration
{
    public function up()
    {
        Schema::dropIfExists('saybme_ub_form_inputs');
    }
    
    public function down()
    {
        Schema::create('saybme_ub_form_inputs', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('hash', 255)->nullable();
            $table->integer('formgoup_id')->nullable();
        });
    }
}
