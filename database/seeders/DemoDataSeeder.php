<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Helpdesk\Models\Company;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\KnowledgeBaseArticle;
use App\Modules\Helpdesk\Models\KnowledgeBaseCategory;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'acme')->with('brands')->first();
        if (! $tenant) {
            return;
        }

        $brand = $tenant->brands->first();

        $admin = User::firstOrCreate([
            'email' => 'admin@acme.test',
        ], [
            'name' => 'ACME Admin',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'brand_id' => $brand?->id,
        ]);
        $admin->assignRole('Admin');

        $agent = User::firstOrCreate([
            'email' => 'agent@acme.test',
        ], [
            'name' => 'ACME Agent',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'brand_id' => $brand?->id,
        ]);
        $agent->assignRole('Agent');

        $viewer = User::firstOrCreate([
            'email' => 'viewer@acme.test',
        ], [
            'name' => 'ACME Viewer',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'brand_id' => $brand?->id,
        ]);
        $viewer->assignRole('Viewer');

        $company = Company::firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => 'Wayne Enterprises',
        ], [
            'brand_id' => $brand?->id,
            'domain' => 'wayne.test',
        ]);

        $contact = Contact::firstOrCreate([
            'tenant_id' => $tenant->id,
            'email' => 'bruce@wayne.test',
        ], [
            'brand_id' => $brand?->id,
            'company_id' => $company->id,
            'name' => 'Bruce Wayne',
            'phone' => '555-0100',
        ]);

        $category = KnowledgeBaseCategory::firstOrCreate([
            'tenant_id' => $tenant->id,
            'slug' => 'getting-started',
        ], [
            'brand_id' => $brand?->id,
            'name' => 'Getting Started',
            'description' => 'Helpful onboarding articles.',
        ]);

        $category = KnowledgeBaseCategory::where('tenant_id', $tenant->id)->first();

        KnowledgeBaseArticle::firstOrCreate([
            'tenant_id' => $tenant->id,
            'slug' => 'welcome',
        ], [
            'brand_id' => $brand?->id,
            'category_id' => $category?->id,
            'title' => 'Welcome to the Helpdesk',
            'content' => 'This is a sample knowledge base article.',
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
        ]);

        Ticket::firstOrCreate([
            'tenant_id' => $tenant->id,
            'reference' => 'ACME-1000',
        ], [
            'brand_id' => $brand?->id,
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'created_by' => $admin->id,
            'assigned_to' => $agent->id,
            'subject' => 'Demo ticket',
            'description' => 'A seeded demo ticket for the helpdesk.',
            'status' => Ticket::STATUS_OPEN,
            'priority' => 'high',
        ]);
    }
}
