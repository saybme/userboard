<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbFormrows extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_formrows', function($table)
        {
            $table->string('hash')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_formrows', function($table)
        {
            $table->dropColumn('hash');
        });
    }
}
