<?php namespace Saybme\Ub\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSaybmeUbApplications6 extends Migration
{
    public function up()
    {
        Schema::table('saybme_ub_applications', function ($table) {
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::table('saybme_ub_applications', function ($table) {
            $table->dropIndex(['user_id']);
        });
    }
}
