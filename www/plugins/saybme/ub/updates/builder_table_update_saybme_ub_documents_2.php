<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbDocuments2 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_documents', function($table)
        {
            $table->integer('form_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_documents', function($table)
        {
            $table->dropColumn('form_id');
        });
    }
}
