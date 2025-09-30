<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'brand_id' => null,
            'name' => $this->faker->company,
            'domain' => $this->faker->domainName,
            'notes' => $this->faker->sentence,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Company $company): void {
            if ($company->brand_id && ! $company->tenant_id) {
                $brand = Brand::find($company->brand_id) ?? Brand::factory()->create();
                $company->tenant_id = $brand->tenant_id;
            }
        })->afterCreating(function (Company $company): void {
            if ($company->brand_id && ! $company->tenant_id) {
                $brand = Brand::find($company->brand_id);
                if ($brand) {
                    $company->tenant_id = $brand->tenant_id;
                    $company->save();
                }
            }
        });
    }

    public function forBrand(Brand $brand): self
    {
        return $this->state(fn () => [
            'tenant_id' => $brand->tenant_id,
            'brand_id' => $brand->id,
        ]);
    }
}
