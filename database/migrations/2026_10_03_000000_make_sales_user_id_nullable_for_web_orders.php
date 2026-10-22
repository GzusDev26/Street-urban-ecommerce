<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE sales DROP FOREIGN KEY sales_user_id_foreign');
        DB::statement('ALTER TABLE sales MODIFY user_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sales DROP FOREIGN KEY sales_user_id_foreign');
        DB::statement('ALTER TABLE sales MODIFY user_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }
};
