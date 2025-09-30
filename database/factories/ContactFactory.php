<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Company;
use App\Modules\Helpdesk\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'brand_id' => null,
            'company_id' => null,
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'title' => $this->faker->jobTitle,
        ];
    }

    public function forCompany(Company $company): self
    {
        return $this->state(fn () => [
            'tenant_id' => $company->tenant_id,
            'brand_id' => $company->brand_id,
            'company_id' => $company->id,
        ]);
    }

    public function forBrand(Brand $brand): self
    {
        return $this->state(fn () => [
            'tenant_id' => $brand->tenant_id,
            'brand_id' => $brand->id,
        ]);
    }
}
