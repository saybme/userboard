<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbReviews extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_reviews', function($table)
        {
            $table->boolean('is_active')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('saybme_ub_reviews', function($table)
        {
            $table->dropColumn('is_active');
        });
    }
}
