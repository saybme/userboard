<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbServises2 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_servises', function($table)
        {
            $table->boolean('is_popular')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_servises', function($table)
        {
            $table->dropColumn('is_popular');
        });
    }
}
