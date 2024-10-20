<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbUsers extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_users', function($table)
        {
            $table->increments('id')->unsigned();
            $table->string('login')->nullable();
            $table->string('phone')->nullable();
            $table->text('profile')->nullable();
            $table->boolean('is_active')->nullable();
            $table->integer('sort_order')->nullable();
            $table->string('password')->nullable();
            $table->string('hash')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('saybme_ub_users');
    }
}
