<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteSaybmeUbFormGroups extends Migration
{
    public function up()
    {
        Schema::dropIfExists('saybme_ub_form_groups');
    }
    
    public function down()
    {
        Schema::create('saybme_ub_form_groups', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->nullable();
            $table->integer('ubform_id')->nullable();
        });
    }
}
