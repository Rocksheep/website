<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureUniquePaymentIntentOnEnrollments extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::table('enrollments', static function (Blueprint $table) {
            $table->unique('payment_intent');
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::table('enrollments', static function (Blueprint $table) {
            $table->dropUnique(['payment_intent']);
        });
    }
}
