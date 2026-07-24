<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;   // her test kendi transaction'ina sarilir, testler birbirini etkilemez

    public function test_giris_yapmis_kullanici_sirket_listesini_gorebilir(): void
    {
        $user = User::factory()->create();
        Company::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/companies');

        $response->assertStatus(200);
    }

    public function test_giris_yapmis_kullanici_sirket_olusturabilir(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/companies', [
            'name' => 'Test A.Ş.',
            'city' => 'İzmir',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('companies', ['name' => 'Test A.Ş.']);
    }

    public function test_admin_olmayan_kullanici_sirket_silemez(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $company = Company::factory()->create();

        $response = $this->actingAs($user)->delete("/companies/{$company->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('companies', ['id' => $company->id]);   // silinmedigini dogrula
    }
}
