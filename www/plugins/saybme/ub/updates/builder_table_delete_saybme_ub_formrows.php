<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteSaybmeUbFormrows extends Migration
{
    public function up()
    {
        Schema::dropIfExists('saybme_ub_formrows');
    }
    
    public function down()
    {
        Schema::create('saybme_ub_formrows', function($table)
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
