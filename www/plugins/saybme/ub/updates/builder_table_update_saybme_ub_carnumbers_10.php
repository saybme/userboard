<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbCarnumbers10 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_carnumbers', function($table)
        {
            $table->text('region')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_carnumbers', function($table)
        {
            $table->dropColumn('region');
        });
    }
}
