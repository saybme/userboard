<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbFormGroups extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_form_groups', function($table)
        {
            $table->renameColumn('form_id', 'ubform_id');
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_form_groups', function($table)
        {
            $table->renameColumn('ubform_id', 'form_id');
        });
    }
}
