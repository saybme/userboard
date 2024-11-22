<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbForminputs2 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_forminputs', function($table)
        {
            $table->renameColumn('form_id', 'ubform_id');
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_forminputs', function($table)
        {
            $table->renameColumn('ubform_id', 'form_id');
        });
    }
}
