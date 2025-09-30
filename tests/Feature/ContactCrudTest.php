<?php

namespace Tests\Feature;

use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Company;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_crud_operations(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $company = Company::factory()->forBrand($brand)->create();

        $contact = Contact::factory()->forCompany($company)->create([
            'name' => 'Clark Kent',
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Clark Kent',
        ]);

        $contact->update([
            'phone' => '555-0123',
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'phone' => '555-0123',
        ]);

        $contactId = $contact->id;
        $contact->delete();

        $this->assertDatabaseMissing('contacts', [
            'id' => $contactId,
        ]);
    }
}
