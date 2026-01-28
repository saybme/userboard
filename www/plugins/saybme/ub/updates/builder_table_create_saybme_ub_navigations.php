<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbNavigations extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_navigations', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('title')->nullable();
            $table->string('link')->nullable();
            $table->text('description')->nullable();
            $table->text('props')->nullable();
            $table->integer('parent_id')->nullable()->unsigned();
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('saybme_ub_navigations');
    }
}
