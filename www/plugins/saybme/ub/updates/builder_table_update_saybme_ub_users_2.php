<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbUsers2 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_users', function($table)
        {
            $table->integer('utype_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_users', function($table)
        {
            $table->dropColumn('utype_id');
        });
    }
}
