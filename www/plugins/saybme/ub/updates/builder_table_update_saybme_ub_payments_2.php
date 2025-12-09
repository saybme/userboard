<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbPayments2 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_payments', function($table)
        {
            $table->integer('imageable_id')->nullable();
            $table->string('imageable_type')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_payments', function($table)
        {
            $table->dropColumn('imageable_id');
            $table->dropColumn('imageable_type');
        });
    }
}
