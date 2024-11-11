<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteSaybmeUbSrvCategories extends Migration
{
    public function up()
    {
        Schema::dropIfExists('saybme_ub_srv_categories');
    }
    
    public function down()
    {
        Schema::create('saybme_ub_srv_categories', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->nullable();
            $table->integer('sort_order')->nullable();
            $table->text('props')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
}
