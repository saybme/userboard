<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSaybmeUbAuthChallenges extends Migration
{
    public function up()
    {
        Schema::create('saybme_ub_auth_challenges', function ($table) {
            $table->increments('id')->unsigned();
            $table->string('provider', 32);
            $table->string('start_token_hash', 64)->unique();
            $table->string('completion_token_hash', 64)->nullable()->unique();
            $table->string('expected_phone', 32);
            $table->string('verified_phone', 32)->nullable();
            $table->string('provider_user_id', 64)->nullable();
            $table->string('telegram_chat_id', 64)->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('session_id', 128)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['provider', 'status']);
            $table->index(['provider', 'provider_user_id', 'status']);
            $table->index('expected_phone');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('saybme_ub_auth_challenges');
    }
}
