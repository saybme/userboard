<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbServises5 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_servises', function($table)
        {
            $table->renameColumn('path', 'path_url');
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_servises', function($table)
        {
            $table->renameColumn('path_url', 'path');
        });
    }
}
