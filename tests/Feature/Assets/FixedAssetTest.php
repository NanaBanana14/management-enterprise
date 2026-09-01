<?php

namespace Tests\Feature\Assets;

use App\Enums\AssetCategory;
use App\Models\Account;
use App\Models\Employee;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\FixedAssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FixedAssetTest extends TestCase
{
    use RefreshDatabase;

    private function seedAccounts(): void
    {
        Account::create(['code' => '1500', 'name' => 'Fixed Assets', 'type' => 'asset']);
        Account::create(['code' => '1510', 'name' => 'Accumulated Depreciation', 'type' => 'asset']);
        Account::create(['code' => '5400', 'name' => 'Depreciation Expense', 'type' => 'expense']);
        Account::create(['code' => '5500', 'name' => 'Loss on Disposal of Assets', 'type' => 'expense']);
        Account::create(['code' => '4200', 'name' => 'Gain on Disposal of Assets', 'type' => 'revenue']);
        Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_cash_bank' => true]);
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::create(['code' => 'WH-FA', 'name' => 'Test Warehouse']);
    }

    public function test_registering_an_asset_stores_it_with_a_generated_code_and_zero_depreciation()
    {
        $this->seedAccounts();
        $user = User::factory()->create();

        $asset = app(FixedAssetService::class)->register(
            'Test Laptop',
            AssetCategory::Equipment,
            null,
            $this->warehouse(),
            null,
            now()->subMonths(3)->toDateString(),
            12_000_000,
            1_200_000,
            36,
            $user,
        );

        $this->assertStringStartsWith('FA-', $asset->code);
        $this->assertSame(0.0, (float) $asset->accumulated_depreciation);
        $this->assertSame('active', $asset->status);
    }

    public function test_running_depreciation_posts_one_journal_entry_and_updates_accumulated_depreciation()
    {
        $this->seedAccounts();
        $user = User::factory()->create();
        $service = app(FixedAssetService::class);

        $warehouse = $this->warehouse();
        $assetOne = $service->register('Asset A', AssetCategory::Equipment, null, $warehouse, null, now()->subMonths(2)->toDateString(), 12_000_000, 0, 24, $user);
        $assetTwo = $service->register('Asset B', AssetCategory::Furniture, null, $warehouse, null, now()->subMonths(2)->toDateString(), 6_000_000, 0, 12, $user);

        $period = now()->subMonth()->startOfMonth()->toDateString();
        $result = $service->runDepreciation($period, $user);

        $this->assertSame(2, $result['count']);
        $this->assertSame(500_000.0 + 500_000.0, $result['total']);
        $this->assertNotNull($result['journal_entry_id']);

        $this->assertSame(500_000.0, (float) $assetOne->fresh()->accumulated_depreciation);
        $this->assertSame(500_000.0, (float) $assetTwo->fresh()->accumulated_depreciation);
        $this->assertSame(1, $assetOne->fresh()->depreciationEntries()->count());
    }

    public function test_running_depreciation_twice_for_the_same_period_is_a_no_op_the_second_time()
    {
        $this->seedAccounts();
        $user = User::factory()->create();
        $service = app(FixedAssetService::class);

        $asset = $service->register('Asset A', AssetCategory::Equipment, null, $this->warehouse(), null, now()->subMonths(2)->toDateString(), 12_000_000, 0, 24, $user);

        $period = now()->subMonth()->startOfMonth()->toDateString();
        $service->runDepreciation($period, $user);
        $second = $service->runDepreciation($period, $user);

        $this->assertSame(0, $second['count']);
        $this->assertNull($second['journal_entry_id']);
        $this->assertSame(500_000.0, (float) $asset->fresh()->accumulated_depreciation);
        $this->assertSame(1, $asset->fresh()->depreciationEntries()->count());
    }

    public function test_disposing_an_asset_posts_a_balanced_journal_entry_and_marks_it_disposed()
    {
        $this->seedAccounts();
        $user = User::factory()->create();
        $service = app(FixedAssetService::class);

        $asset = $service->register('Asset A', AssetCategory::Equipment, null, $this->warehouse(), null, now()->subMonths(3)->toDateString(), 12_000_000, 0, 24, $user);
        $service->runDepreciation(now()->subMonths(2)->startOfMonth()->toDateString(), $user);

        $disposed = $service->dispose($asset, now()->toDateString(), 10_000_000, $user);

        $this->assertSame('disposed', $disposed->status);
        $this->assertNotNull($disposed->disposal_journal_entry_id);

        $entry = $disposed->disposalJournalEntry;
        $totalDebit = (float) $entry->lines->sum('debit');
        $totalCredit = (float) $entry->lines->sum('credit');
        $this->assertSame($totalDebit, $totalCredit);

        $this->expectException(ValidationException::class);
        $service->dispose($disposed, now()->toDateString(), 1_000_000, $user);
    }

    public function test_reassigning_an_asset_updates_its_custody()
    {
        $this->seedAccounts();
        $user = User::factory()->create();
        $employee = Employee::factory()->create();
        $service = app(FixedAssetService::class);

        $asset = $service->register('Asset A', AssetCategory::Equipment, null, $this->warehouse(), null, now()->toDateString(), 5_000_000, 0, 24, $user);

        $service->reassign($asset, null, $employee);

        $this->assertNull($asset->fresh()->warehouse_id);
        $this->assertSame($employee->id, $asset->fresh()->employee_id);
    }
}
