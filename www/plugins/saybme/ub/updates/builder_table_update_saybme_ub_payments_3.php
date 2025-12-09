<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbPayments3 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_payments', function($table)
        {
            $table->string('link')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_payments', function($table)
        {
            $table->dropColumn('link');
        });
    }
}
