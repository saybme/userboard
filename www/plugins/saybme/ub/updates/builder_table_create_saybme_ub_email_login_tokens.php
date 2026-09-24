<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbEmailLoginTokens extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_email_login_tokens', function ($table) {
            $table->increments('id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('email', 255);
            $table->string('token_hash', 64)->unique();
            $table->string('status', 32)->default('pending');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['email', 'status']);
            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('saybme_ub_email_login_tokens');
    }
}
