<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbMessages extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_messages', function($table)
        {
            $table->integer('suptheme_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_messages', function($table)
        {
            $table->dropColumn('suptheme_id');
        });
    }
}
