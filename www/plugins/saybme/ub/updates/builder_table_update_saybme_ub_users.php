<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbUsers extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_users', function($table)
        {
            $table->string('email')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_users', function($table)
        {
            $table->dropColumn('email');
        });
    }
}
