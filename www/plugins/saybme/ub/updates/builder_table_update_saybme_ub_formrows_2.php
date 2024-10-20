<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbFormrows2 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_formrows', function($table)
        {
            $table->dropColumn('hash');
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_formrows', function($table)
        {
            $table->string('hash', 255)->nullable();
        });
    }
}
