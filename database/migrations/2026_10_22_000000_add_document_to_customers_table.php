<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Documento de identidad del cliente (DNI o RUC) — base para emitir boleta/factura.
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('document_type', 3)->nullable()->after('name');
            $table->string('document_number', 11)->nullable()->unique()->after('document_type');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['document_number']);
            $table->dropColumn(['document_type', 'document_number']);
        });
    }
};
