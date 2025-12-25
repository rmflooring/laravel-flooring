<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountType;

class AccountTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Asset',
                'category' => 'Asset',
                'description' => 'Resources owned by the business with economic value (e.g., cash, inventory, equipment).',
                'status' => 'active',
            ],
            [
                'name' => 'Liability',
                'category' => 'Liability',
                'description' => 'Obligations or debts owed to others (e.g., loans, accounts payable).',
                'status' => 'active',
            ],
            [
                'name' => 'Equity',
                'category' => 'Equity',
                'description' => 'Owner\'s residual interest in the business after liabilities are deducted from assets.',
                'status' => 'active',
            ],
            [
                'name' => 'Income',
                'category' => 'Income',
                'description' => 'Revenues or gains from business operations (e.g., sales, service income).',
                'status' => 'active',
            ],
            [
                'name' => 'Expense',
                'category' => 'Expense',
                'description' => 'Costs incurred in generating revenue (e.g., rent, salaries, materials).',
                'status' => 'active',
            ],
        ];

        foreach ($types as $type) {
            AccountType::firstOrCreate(
                ['name' => $type['name']], // Prevent duplicates
                $type
            );
        }
    }
}
