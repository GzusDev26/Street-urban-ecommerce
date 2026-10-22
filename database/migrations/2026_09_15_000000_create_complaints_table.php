<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_number', 30)->nullable()->unique();

            $table->string('claimant_name');
            $table->string('claimant_address', 500);
            $table->string('claimant_national_id', 30);
            $table->string('claimant_email');
            $table->string('claimant_phone', 30)->nullable();

            $table->string('purchased_item', 500);
            $table->decimal('claimed_amount', 12, 2)->nullable();
            $table->enum('claim_type', ['Queja', 'Reclamo']);
            $table->text('claim_details');
            $table->text('claim_request');

            $table->timestamp('privacy_accepted_at');
            $table->string('status', 30)->default('Pendiente');
            $table->timestamp('submitted_at');
            $table->timestamp('response_due_at');

            $table->timestamps();

            $table->index('claimant_email');
            $table->index('claimant_national_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
