<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbFormvalues2 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_formvalues', function($table)
        {
            $table->string('price_title')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_formvalues', function($table)
        {
            $table->dropColumn('price_title');
        });
    }
}
