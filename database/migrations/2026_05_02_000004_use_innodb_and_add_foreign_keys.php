<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'companies', 'inventories', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'migrations', 'password_reset_tokens'] as $table) {
            DB::statement("ALTER TABLE {$table} ENGINE=InnoDB");
        }

        DB::statement('DELETE inventories FROM inventories LEFT JOIN companies ON inventories.company_id = companies.id WHERE companies.id IS NULL');
        DB::statement('DELETE companies FROM companies LEFT JOIN users ON companies.owner_user_id = users.id WHERE users.id IS NULL');
        DB::statement('DELETE inventories FROM inventories LEFT JOIN companies ON inventories.company_id = companies.id WHERE companies.id IS NULL');
        DB::statement('UPDATE users LEFT JOIN companies ON users.company_id = companies.id SET users.company_id = NULL WHERE users.company_id IS NOT NULL AND companies.id IS NULL');

        $this->addForeignKey('users_company_id_foreign', 'users', 'company_id', 'companies', 'id', 'SET NULL');
        $this->addForeignKey('companies_owner_user_id_foreign', 'companies', 'owner_user_id', 'users', 'id', 'CASCADE');
        $this->addForeignKey('inventories_company_id_foreign', 'inventories', 'company_id', 'companies', 'id', 'CASCADE');
    }

    public function down(): void
    {
        foreach (['inventories_company_id_foreign' => 'inventories', 'companies_owner_user_id_foreign' => 'companies', 'users_company_id_foreign' => 'users'] as $constraint => $table) {
            if ($this->hasForeignKey($constraint)) {
                DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
            }
        }
    }

    private function addForeignKey(string $constraint, string $table, string $column, string $referencesTable, string $referencesColumn, string $onDelete): void
    {
        if (! $this->hasForeignKey($constraint)) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES {$referencesTable} ({$referencesColumn}) ON DELETE {$onDelete}");
        }
    }

    private function hasForeignKey(string $constraint): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::selectOne('SELECT COUNT(*) AS total FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?', [$database, $constraint, 'FOREIGN KEY']);

        return (int) $result->total > 0;
    }
};
