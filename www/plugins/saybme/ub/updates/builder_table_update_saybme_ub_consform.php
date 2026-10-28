<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbConsform extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_consform', function($table)
        {
            $table->string('slug')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_consform', function($table)
        {
            $table->dropColumn('slug');
        });
    }
}
