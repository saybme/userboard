<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbFormvalues extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_formvalues', function($table)
        {
            $table->integer('price')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_formvalues', function($table)
        {
            $table->dropColumn('price');
        });
    }
}
