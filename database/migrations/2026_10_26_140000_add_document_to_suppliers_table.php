<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RUC (o DNI, si el proveedor es una persona) del proveedor: sirve para encontrarlo al comprar sin repetirlo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('document_type', 3)->nullable()->after('name');
            $table->string('document_number', 11)->nullable()->unique()->after('document_type');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique(['document_number']);
            $table->dropColumn(['document_type', 'document_number']);
        });
    }
};
