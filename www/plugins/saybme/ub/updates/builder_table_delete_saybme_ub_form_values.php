<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteSaybmeUbFormValues extends Migration
{
    public function up()
    {
        Schema::dropIfExists('saybme_ub_form_values');
    }
    
    public function down()
    {
        Schema::create('saybme_ub_form_values', function($table)
        {
            $table->increments('id')->unsigned();
            $table->integer('parent_id')->nullable()->unsigned();
            $table->text('value')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
}
