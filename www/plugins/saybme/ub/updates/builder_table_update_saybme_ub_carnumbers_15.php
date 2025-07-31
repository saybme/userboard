<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbCarnumbers15 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_carnumbers', function($table)
        {
            $table->integer('sum_start')->default(0)->change();
            $table->integer('sum_end')->default(0)->change();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_carnumbers', function($table)
        {
            $table->integer('sum_start')->default(null)->change();
            $table->integer('sum_end')->default(null)->change();
        });
    }
}
