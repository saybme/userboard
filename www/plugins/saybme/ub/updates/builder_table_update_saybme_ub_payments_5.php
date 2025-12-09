<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbPayments5 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_payments', function($table)
        {
            $table->string('status', 10)->nullable()->unsigned(false)->default(null)->comment(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_payments', function($table)
        {
            $table->integer('status')->nullable()->unsigned(false)->default(null)->comment(null)->change();
        });
    }
}
