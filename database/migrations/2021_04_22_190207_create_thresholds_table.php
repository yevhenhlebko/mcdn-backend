<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThresholdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('thresholds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('device_id');
            $table->unsignedBigInteger('tag_id');
            $table->string('operator');
            $table->float('value');
            $table->json('sms_info');
            $table->json('email_info');
            $table->boolean('status')->default('true');
            $table->integer('offset')->default(0);
            $table->integer('multipled_by')->default(1);
            $table->string('serial_number', 20)->default('');
            $table->integer('bytes')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('thresholds');
    }
}
