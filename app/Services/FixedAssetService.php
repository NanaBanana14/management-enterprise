<?php

namespace App\Services;

use App\Enums\AssetCategory;
use App\Models\Account;
use App\Models\Employee;
use App\Models\FixedAsset;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FixedAssetService
{
    public function __construct(private JournalService $journal) {}

    public function register(
        string $name,
        AssetCategory $category,
        ?string $description,
        ?Warehouse $warehouse,
        ?Employee $employee,
        string $acquisitionDate,
        float $cost,
        float $salvageValue,
        int $usefulLifeMonths,
        User $creator,
    ): FixedAsset {
        if ($cost <= 0) {
            throw ValidationException::withMessages(['acquisition_cost' => 'Acquisition cost must be greater than zero.']);
        }

        if ($usefulLifeMonths <= 0) {
            throw ValidationException::withMessages(['useful_life_months' => 'Useful life must be at least one month.']);
        }

        if ($salvageValue >= $cost) {
            throw ValidationException::withMessages(['salvage_value' => 'Salvage value must be less than the acquisition cost.']);
        }

        return DB::transaction(function () use ($name, $category, $description, $warehouse, $employee, $acquisitionDate, $cost, $salvageValue, $usefulLifeMonths, $creator) {
            $code = 'FA-'.now()->format('Ym').'-'.Str::padLeft((string) (FixedAsset::count() + 1), 4, '0');

            return FixedAsset::create([
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'description' => $description,
                'warehouse_id' => $warehouse?->id,
                'employee_id' => $employee?->id,
                'acquisition_date' => $acquisitionDate,
                'acquisition_cost' => $cost,
                'salvage_value' => $salvageValue,
                'useful_life_months' => $usefulLifeMonths,
                'accumulated_depreciation' => 0,
                'status' => 'active',
                'created_by' => $creator->id,
            ]);
        });
    }

    public function reassign(FixedAsset $asset, ?Warehouse $warehouse, ?Employee $employee): FixedAsset
    {
        $asset->update(['warehouse_id' => $warehouse?->id, 'employee_id' => $employee?->id]);

        return $asset;
    }

    /**
     * @return array{count: int, total: float, journal_entry_id: ?int}
     */
    public function runDepreciation(string $period, User $user): array
    {
        $period = Carbon::parse($period)->startOfMonth()->toDateString();

        return DB::transaction(function () use ($period, $user) {
            $assets = FixedAsset::query()
                ->where('status', 'active')
                ->whereDate('acquisition_date', '<=', $period)
                ->whereDoesntHave('depreciationEntries', fn ($q) => $q->whereDate('period', $period))
                ->lockForUpdate()
                ->get();

            $amounts = [];
            $total = 0.0;

            foreach ($assets as $asset) {
                $depreciable = (float) $asset->acquisition_cost - (float) $asset->salvage_value;
                $remaining = $depreciable - (float) $asset->accumulated_depreciation;

                if ($remaining <= 0) {
                    continue;
                }

                $monthly = round($depreciable / $asset->useful_life_months, 2);
                $amount = min($monthly, $remaining);

                if ($amount <= 0) {
                    continue;
                }

                $amounts[$asset->id] = $amount;
                $total += $amount;
            }

            if ($total <= 0) {
                return ['count' => 0, 'total' => 0.0, 'journal_entry_id' => null];
            }

            $entry = $this->journal->create(
                $period,
                'DEP-'.now()->format('Ym').'-'.Str::upper(Str::random(6)),
                'Depreciation for '.Carbon::parse($period)->format('F Y'),
                [
                    ['account_id' => $this->depreciationExpenseAccount()->id, 'debit' => $total, 'credit' => 0],
                    ['account_id' => $this->accumulatedDepreciationAccount()->id, 'debit' => 0, 'credit' => $total],
                ],
                $user,
            );

            foreach ($amounts as $assetId => $amount) {
                $asset = $assets->firstWhere('id', $assetId);

                $asset->depreciationEntries()->create([
                    'period' => $period,
                    'amount' => $amount,
                    'journal_entry_id' => $entry->id,
                ]);

                $asset->update(['accumulated_depreciation' => (float) $asset->accumulated_depreciation + $amount]);
            }

            return ['count' => count($amounts), 'total' => $total, 'journal_entry_id' => $entry->id];
        });
    }

    public function dispose(FixedAsset $asset, string $disposalDate, float $disposalValue, User $user): FixedAsset
    {
        return DB::transaction(function () use ($asset, $disposalDate, $disposalValue, $user) {
            $asset = FixedAsset::query()->whereKey($asset->id)->lockForUpdate()->firstOrFail();

            if ($asset->status !== 'active') {
                throw ValidationException::withMessages(['status' => 'Only active assets can be disposed.']);
            }

            $accumulatedDepreciation = (float) $asset->accumulated_depreciation;
            $bookValue = (float) $asset->acquisition_cost - $accumulatedDepreciation;
            $gainLoss = $disposalValue - $bookValue;

            $entry = $this->journal->create(
                $disposalDate,
                'DISP-'.now()->format('Ym').'-'.Str::upper(Str::random(6)),
                "Disposal of asset {$asset->code}",
                [
                    ['account_id' => $this->accumulatedDepreciationAccount()->id, 'debit' => $accumulatedDepreciation, 'credit' => 0],
                    ['account_id' => $this->cashBankAccount()->id, 'debit' => $disposalValue, 'credit' => 0],
                    ['account_id' => $this->lossOnDisposalAccount()->id, 'debit' => max(0, -$gainLoss), 'credit' => 0],
                    ['account_id' => $this->fixedAssetsAccount()->id, 'debit' => 0, 'credit' => (float) $asset->acquisition_cost],
                    ['account_id' => $this->gainOnDisposalAccount()->id, 'debit' => 0, 'credit' => max(0, $gainLoss)],
                ],
                $user,
            );

            $asset->update([
                'status' => 'disposed',
                'disposal_date' => $disposalDate,
                'disposal_value' => $disposalValue,
                'disposal_journal_entry_id' => $entry->id,
            ]);

            return $asset;
        });
    }

    private function fixedAssetsAccount(): Account
    {
        return Account::where('code', '1500')->firstOrFail();
    }

    private function accumulatedDepreciationAccount(): Account
    {
        return Account::where('code', '1510')->firstOrFail();
    }

    private function depreciationExpenseAccount(): Account
    {
        return Account::where('code', '5400')->firstOrFail();
    }

    private function lossOnDisposalAccount(): Account
    {
        return Account::where('code', '5500')->firstOrFail();
    }

    private function gainOnDisposalAccount(): Account
    {
        return Account::where('code', '4200')->firstOrFail();
    }

    private function cashBankAccount(): Account
    {
        return Account::where('is_cash_bank', true)->orderBy('code')->firstOrFail();
    }
}
