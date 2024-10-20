<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbSrvCategories extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_srv_categories', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->nullable();
            $table->integer('sort_order')->nullable();
            $table->text('props')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('saybme_ub_srv_categories');
    }
}
