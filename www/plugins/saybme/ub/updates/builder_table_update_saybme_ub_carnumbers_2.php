<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbCarnumbers2 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_carnumbers', function($table)
        {
            $table->string('type')->nullable();
            $table->integer('pay')->nullable();
            $table->integer('urgently')->nullable();
            $table->string('username')->nullable();
            $table->integer('sum_start')->nullable();
            $table->integer('sum_end')->nullable();
            $table->text('address')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_carnumbers', function($table)
        {
            $table->dropColumn('type');
            $table->dropColumn('pay');
            $table->dropColumn('urgently');
            $table->dropColumn('username');
            $table->dropColumn('sum_start');
            $table->dropColumn('sum_end');
            $table->dropColumn('address');
        });
    }
}
