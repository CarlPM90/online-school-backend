<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixLanguageLinesTable extends Command
{
    protected $signature = 'fix:language-lines';
    protected $description = 'Fix the language_lines table structure';

    public function handle()
    {
        try {
            DB::statement('DROP TABLE IF EXISTS language_lines');
            
            DB::statement('
                CREATE TABLE language_lines (
                    id BIGSERIAL PRIMARY KEY,
                    "group" VARCHAR(255) NOT NULL,
                    "key" VARCHAR(255) NOT NULL,
                    text TEXT NOT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
            
            DB::statement('CREATE INDEX language_lines_group_key_index ON language_lines ("group", "key")');
            
            $this->info('Language lines table created successfully');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}