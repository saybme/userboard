<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbFormGroups extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_form_groups', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->nullable();
            $table->integer('form_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('saybme_ub_form_groups');
    }
}
