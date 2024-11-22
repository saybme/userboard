<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableDeleteSaybmeUbForms extends Migration
{
    public function up()
    {
        Schema::dropIfExists('saybme_ub_forms');
    }
    
    public function down()
    {
        Schema::create('saybme_ub_forms', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('name', 255)->nullable();
            $table->text('introtext')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->nullable();
            $table->string('tmp', 255)->nullable();
            $table->text('content')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('hash', 255)->nullable();
            $table->string('url', 255)->nullable();
            $table->text('props')->nullable();
            $table->text('tabs')->nullable();
            $table->text('inputs')->nullable();
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
            $table->integer('parent_id')->nullable();
            $table->string('menutitle', 255)->nullable();
        });
    }
}
