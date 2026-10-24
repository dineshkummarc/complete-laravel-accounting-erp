<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Accounting\Actions\ApproveJournal;
use App\Modules\Accounting\Actions\CreateJournal;
use App\Modules\Accounting\Actions\LockPeriod;
use App\Modules\Accounting\Actions\PostJournal;
use App\Modules\Accounting\Actions\RebuildBalances;
use App\Modules\Accounting\Actions\ReverseJournal;
use App\Modules\Accounting\Actions\SetAccountMapping;
use App\Modules\Accounting\Actions\SubmitJournal;
use App\Modules\Accounting\Actions\UpdateAccountingSettings;
use App\Modules\Accounting\DTOs\JournalData;
use App\Modules\Accounting\DTOs\JournalLineData;
use App\Modules\Accounting\Enums\JournalStatus;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Accounting\ValueObjects\Amount;
use App\Modules\Core\Actions\AddAttachment;
use App\Modules\Core\Actions\AddCompanyMember;
use App\Modules\Core\Actions\CreateBranch;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\ReportRegistry;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-10-24 12:00:00');
    $this->owner = User::factory()->create();
    $this->company = businessCompany($this->owner);
    $this->branchId = $this->owner->fresh()->current_branch_id;
    $this->actingAs($this->owner->fresh());
    app(BusinessContext::class)->select(auth()->user(), $this->company->id, $this->branchId);
    $this->cash = Account::query()->where('code', '1100')->firstOrFail();
    $this->sales = Account::query()->where('code', '4100')->firstOrFail();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function ledgerData(int $cash, int $sales, string $amount = '100.0000', ?string $source = null): JournalData
{
    return new JournalData('2026-10-24', 'BDT', '1.0000000000', 'Manual journal', [new JournalLineData($cash, $amount, '0.0000'), new JournalLineData($sales, '0.0000', $amount)], 'manual', $source ?? (string) Str::uuid());
}

it('initializes real chart mappings and twelve fiscal periods without fictional transactions', function () {
    expect(Account::query()->count())->toBeGreaterThan(20);
    expect(FiscalPeriod::query()->count())->toBe(12);
    expect(JournalEntry::query()->count())->toBe(0);
    expect(DB::table('account_mappings')->where('company_id', $this->company->id)->count())->toBeGreaterThan(20);
});

it('posts exact decimal amounts and updates balanced snapshots once', function () {
    $data = new JournalData('2026-10-24', 'BDT', '1', 'Decimal arithmetic', [new JournalLineData($this->cash->id, '0.1000', '0'), new JournalLineData($this->cash->id, '0.2000', '0'), new JournalLineData($this->sales->id, '0', '0.3000')], 'manual', (string) Str::uuid());
    $entry = app(CreateJournal::class)->handle(auth()->user(), $data);
    $posted = app(PostJournal::class)->handle(auth()->user(), $entry->id);
    expect($posted->status)->toBe(JournalStatus::Posted);
    expect($posted->number)->toBe('JV-HQ-2026-000001');
    app(PostJournal::class)->handle(auth()->user(), $entry->id);
    $rows = DB::table('account_balances')->where('company_id', $this->company->id)->get();
    $debit = BigDecimal::zero();
    $credit = BigDecimal::zero();
    foreach ($rows as $row) {
        $debit = $debit->plus($row->debit);
        $credit = $credit->plus($row->credit);
    }
    expect((string) $debit->toScale(4))->toBe('0.3000');
    expect($debit->isEqualTo($credit))->toBeTrue();
    expect(JournalEntry::query()->count())->toBe(1);
});

it('preserves the full DECIMAL(20,4) precision without a float conversion', function () {
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id, '9999999999999999.9999'));
    expect($entry->lines->first()->debit)->toBe('9999999999999999.9999');
    app(PostJournal::class)->handle(auth()->user(), $entry->id);
    expect(DB::table('account_balances')->where('account_id', $this->cash->id)->value('debit'))->toBe('9999999999999999.9999');
});

it('rejects unbalanced posting without using a number or writing snapshots', function () {
    $entry = app(CreateJournal::class)->handle(auth()->user(), new JournalData('2026-10-24', 'BDT', '1', 'Unbalanced draft', [new JournalLineData($this->cash->id, '10', '0'), new JournalLineData($this->sales->id, '0', '9')], 'manual', (string) Str::uuid()));
    expect(fn () => app(PostJournal::class)->handle(auth()->user(), $entry->id))->toThrow(ValidationException::class);
    expect(DB::table('account_balances')->count())->toBe(0);
    expect(DB::table('number_sequences')->count())->toBe(0);
    expect($entry->fresh()->status)->toBe(JournalStatus::Draft);
});

it('deduplicates a source version and refuses changed data at that same version', function () {
    $source = (string) Str::uuid();
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id, '100', $source));
    expect(app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id, '100', $source))->id)->toBe($entry->id);
    expect(fn () => app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id, '200', $source)))->toThrow(ValidationException::class);
});

it('rejects model and bulk mutation of posted entries and lines', function () {
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id));
    app(PostJournal::class)->handle(auth()->user(), $entry->id);
    $entry = $entry->fresh();
    expect(fn () => $entry->update(['narration' => 'Changed']))->toThrow(LogicException::class);
    expect(fn () => $entry->delete())->toThrow(LogicException::class);
    expect(fn () => JournalEntry::query()->whereKey($entry->id)->update(['narration' => 'Changed']))->toThrow(LogicException::class);
    expect(fn () => JournalLine::query()->where('journal_entry_id', $entry->id)->delete())->toThrow(LogicException::class);
    $line = $entry->lines()->first();
    expect(fn () => $line->update(['debit' => '500']))->toThrow(LogicException::class);
    expect(fn () => JournalLine::query()->whereKey($line->id)->increment('debit'))->toThrow(LogicException::class);
});

it('reverses with a new linked entry, preserves the original facts and neutralizes balances', function () {
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id));
    app(PostJournal::class)->handle(auth()->user(), $entry->id);
    $reversal = app(ReverseJournal::class)->handle(auth()->user(), $entry->id, '2026-10-24', 'Correction');
    expect($reversal->status)->toBe(JournalStatus::Posted);
    expect($entry->fresh()->status)->toBe(JournalStatus::Reversed);
    expect($entry->lines()->first()->debit)->toBe('100.0000');
    expect(app(ReverseJournal::class)->handle(auth()->user(), $entry->id, '2026-10-24', 'Retry')->id)->toBe($reversal->id);
    foreach (DB::table('account_balances')->get() as $balance) {
        expect(BigDecimal::of($balance->debit)->isEqualTo($balance->credit))->toBeTrue();
    }
});

it('blocks period-locked posting and requires an explicit audited override', function () {
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id));
    app(LockPeriod::class)->handle(auth()->user(), $entry->fiscal_period_id, true, 'Month closed');
    expect(fn () => app(PostJournal::class)->handle(auth()->user(), $entry->id))->toThrow(ValidationException::class);
    app(PostJournal::class)->handle(auth()->user(), $entry->id, true, 'Authorized correction');
    expect($entry->fresh()->status)->toBe(JournalStatus::Posted);
    expect(DB::table('audit_events')->where('action', 'period.override')->exists())->toBeTrue();
});

it('requires independent maker-checker approval above the company threshold', function () {
    DB::table('companies')->where('id', $this->company->id)->update(['accounting_settings' => '{"maker_checker":true,"approval_threshold":"50.0000"}']);
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id));
    expect(fn () => app(PostJournal::class)->handle(auth()->user(), $entry->id))->toThrow(ValidationException::class);
    app(SubmitJournal::class)->handle(auth()->user(), $entry->id);
    expect(fn () => app(ApproveJournal::class)->handle(auth()->user(), $entry->id))->toThrow(ValidationException::class);
    $checker = User::factory()->create();
    app(AddCompanyMember::class)->handle(auth()->user(), $checker->email, [$this->branchId]);
    $checker->givePermissionTo(['accounting.journal.approve', 'accounting.journal.post']);
    app(BusinessContext::class)->select($checker, $this->company->id, $this->branchId);
    $this->actingAs($checker);
    app(ApproveJournal::class)->handle($checker, $entry->id);
    app(PostJournal::class)->handle($checker, $entry->id);
    expect($entry->fresh()->approved_by)->toBe($checker->id);
    expect($entry->fresh()->status)->toBe(JournalStatus::Posted);
});

it('uses the exchange-rate snapshot for every base amount', function () {
    $data = new JournalData('2026-10-24', 'USD', '123.4567890123', 'Foreign currency', [new JournalLineData($this->cash->id, '10', '0'), new JournalLineData($this->sales->id, '0', '10')], 'manual', (string) Str::uuid());
    $entry = app(CreateJournal::class)->handle(auth()->user(), $data);
    expect($entry->lines->first()->base_debit)->toBe('1234.5679');
    app(PostJournal::class)->handle(auth()->user(), $entry->id);
});

it('prevents accounts or journals crossing the current company or branch', function () {
    $other = businessCompany(User::factory()->create(), 'Other ledger');
    $foreign = app(BusinessContext::class)->run($other->id, null, fn () => Account::query()->where('code', '1100')->value('id'));
    expect(fn () => app(CreateJournal::class)->handle(auth()->user(), ledgerData($foreign, $this->sales->id)))->toThrow(ValidationException::class);
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id));
    $branch = app(CreateBranch::class)->handle(auth()->user(), ['name' => 'Second', 'code' => 'SECOND', 'timezone' => 'UTC']);
    app(BusinessContext::class)->select(auth()->user(), $this->company->id, $branch->id);
    expect(JournalEntry::query()->whereKey($entry->id)->exists())->toBeFalse();
});

it('rejects negative, over-precision and double-sided line values at the DTO boundary', function () {
    expect(fn () => new Amount('-1'))->toThrow(InvalidArgumentException::class);
    expect(fn () => new Amount('0.00001'))->toThrow(InvalidArgumentException::class);
    expect(fn () => new JournalLineData($this->cash->id, '1', '1'))->toThrow(InvalidArgumentException::class);
});

it('reconciles reports with exact posted facts and excludes draft entries', function () {
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id, '0.3000'));
    app(PostJournal::class)->handle(auth()->user(), $entry->id);
    app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id, '900'));
    $registry = app(ReportRegistry::class);
    $params = ['from' => '2026-10-01', 'to' => '2026-10-31'];
    $trial = $registry->get('trial-balance')->query(auth()->user(), $params);
    expect($trial->totals['debit'])->toBe('0.3000')->and($trial->totals['credit'])->toBe('0.3000');
    $income = $registry->get('income-statement')->query(auth()->user(), $params);
    expect($income->totals['profit'])->toBe('0.3000');
    $sheet = $registry->get('balance-sheet')->query(auth()->user(), $params);
    expect($sheet->totals['asset'])->toBe('0.3000')->and($sheet->totals['equity'])->toBe('0.3000');
    $cash = $registry->get('cash-flow')->query(auth()->user(), $params);
    expect($cash->rows[2]['amount'])->toBe('0.3000');
    $limited = $registry->get('general-ledger')->query(auth()->user(), $params, 1);
    expect($limited->rows)->toHaveCount(1)->and($limited->metadata['row_count'])->toBe(2)->and($limited->metadata['truncated'])->toBeTrue();
});

it('uses opening balances and rejects unauthorized report branches', function () {
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id, '23.4567'));
    app(PostJournal::class)->handle(auth()->user(), $entry->id);
    $registry = app(ReportRegistry::class);
    $trial = $registry->get('trial-balance')->query(auth()->user(), ['from' => '2026-11-01', 'to' => '2026-11-30']);
    expect($trial->totals['opening_debit'])->toBe('23.4567')->and($trial->totals['debit'])->toBe('0.0000')->and($trial->totals['closing_credit'])->toBe('23.4567');
    expect(fn () => $registry->get('trial-balance')->query(auth()->user(), ['branch_ids' => [999999]]))->toThrow(AuthorizationException::class);
});

it('rebuilds tampered snapshots from immutable posted lines', function () {
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id, '45.1234'));
    app(PostJournal::class)->handle(auth()->user(), $entry->id);
    DB::table('account_balances')->update(['debit' => '999.0000']);
    expect(app(RebuildBalances::class)->handle(auth()->user()))->toBe(2);
    expect(DB::table('account_balances')->where('account_id', $this->cash->id)->value('debit'))->toBe('45.1234');
    expect(DB::table('account_balances')->where('account_id', $this->sales->id)->value('debit'))->toBe('0.0000');
});

it('records bounded foreign currency conversion rounding as an explicit base-only line', function () {
    $data = new JournalData('2026-10-24', 'USD', '1.2345', 'FX rounding', [new JournalLineData($this->cash->id, '0.0001', '0'), new JournalLineData($this->cash->id, '0.0001', '0'), new JournalLineData($this->sales->id, '0', '0.0002')], 'manual', (string) Str::uuid());
    $entry = app(CreateJournal::class)->handle(auth()->user(), $data);
    // 0.0001 * 1.2345 rounds to 0.0001; 0.0002 * 1.2345 also rounds to 0.0002.
    expect($entry->lines)->toHaveCount(3);
    $data = new JournalData('2026-10-24', 'USD', '1.5000', 'FX rounding', [new JournalLineData($this->cash->id, '0.0001', '0'), new JournalLineData($this->cash->id, '0.0001', '0'), new JournalLineData($this->sales->id, '0', '0.0002')], 'manual', (string) Str::uuid());
    $entry = app(CreateJournal::class)->handle(auth()->user(), $data);
    $rounding = $entry->lines->firstWhere('is_rounding', true);
    expect($rounding)->not->toBeNull()->and($rounding->credit)->toBe('0.0000')->and($rounding->base_credit)->toBe('0.0001');
    expect(app(PostJournal::class)->handle(auth()->user(), $entry->id)->status)->toBe(JournalStatus::Posted);
});

it('persists validated approval settings and enforces the threshold', function () {
    app(UpdateAccountingSettings::class)->handle(auth()->user(), ['maker_checker' => true, 'approval_threshold' => '50.0000']);
    $small = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id, '49.9999'));
    expect(app(PostJournal::class)->handle(auth()->user(), $small->id)->status)->toBe(JournalStatus::Posted);
    $large = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id, '50.0000'));
    expect(fn () => app(PostJournal::class)->handle(auth()->user(), $large->id))->toThrow(ValidationException::class);
});

it('includes journals on both report date boundaries and stores date-only facts consistently', function () {
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id, '12.3456'));
    app(PostJournal::class)->handle(auth()->user(), $entry->id);
    expect(DB::table('journal_entries')->where('id', $entry->id)->value('entry_date'))->toBe('2026-10-24');
    $report = app(ReportRegistry::class)->get('trial-balance')->query(auth()->user(), ['from' => '2026-10-24', 'to' => '2026-10-24']);
    expect($report->totals['debit'])->toBe('12.3456')->and($report->totals['credit'])->toBe('12.3456');
    $period = FiscalPeriod::query()->orderBy('starts_on')->firstOrFail();
    expect(DB::table('fiscal_periods')->where('id', $period->id)->value('starts_on'))->toBe($period->starts_on->toDateString());
});

it('attaches private scanned voucher evidence without changing posted facts', function () {
    Storage::fake('local');
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id));
    app(PostJournal::class)->handle(auth()->user(), $entry->id);
    $file = UploadedFile::fake()->createWithContent('voucher.txt', 'Disposable voucher evidence');
    $attachment = app(AddAttachment::class)->handle(auth()->user(), $entry->fresh(), $file);
    expect($attachment->subject_type)->toBe(JournalEntry::class)->and($attachment->subject_id)->toBe($entry->id)->and($entry->fresh()->status)->toBe(JournalStatus::Posted);
    expect(auth()->user()->can('view', $attachment))->toBeTrue();
    Storage::disk('local')->assertExists($attachment->path);
});

it('rejects unsupported currencies and conversion overflow as validation before any journal write', function () {
    $unsupported = new JournalData('2026-10-24', 'ZZZ', '1', 'Unknown currency', [new JournalLineData($this->cash->id, '1', '0'), new JournalLineData($this->sales->id, '0', '1')], 'manual', (string) Str::uuid());
    expect(fn () => app(CreateJournal::class)->handle(auth()->user(), $unsupported))->toThrow(ValidationException::class);
    $overflow = new JournalData('2026-10-24', 'USD', '2', 'Overflow', [new JournalLineData($this->cash->id, '9999999999999999.9999', '0'), new JournalLineData($this->sales->id, '0', '9999999999999999.9999')], 'manual', (string) Str::uuid());
    expect(fn () => app(CreateJournal::class)->handle(auth()->user(), $overflow))->toThrow(ValidationException::class);
    expect(JournalEntry::query()->count())->toBe(0);
});

it('denies voucher attachment downloads to branch members without ledger permission', function () {
    Storage::fake('local');
    $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($this->cash->id, $this->sales->id));
    $attachment = app(AddAttachment::class)->handle(auth()->user(), $entry, UploadedFile::fake()->createWithContent('voucher.txt', 'Evidence'));
    $staff = User::factory()->create();
    app(AddCompanyMember::class)->handle(auth()->user(), $staff->email, [$this->branchId]);
    $this->actingAs($staff);
    app(BusinessContext::class)->select($staff, $this->company->id, $this->branchId);
    expect($staff->can('view', $attachment))->toBeFalse()->and($staff->can('attach', $entry))->toBeFalse();
});

it('honors branch account mapping overrides when filtering bank books', function () {
    $bank = Account::query()->where('code', '1110')->firstOrFail();
    $wallet = Account::query()->where('code', '1120')->firstOrFail();
    app(SetAccountMapping::class)->handle(auth()->user(), 'bank', $wallet->id, true);
    foreach ([$bank, $wallet] as $account) {
        $entry = app(CreateJournal::class)->handle(auth()->user(), ledgerData($account->id, $this->sales->id, '1'));
        app(PostJournal::class)->handle(auth()->user(), $entry->id);
    }
    $report = app(ReportRegistry::class)->get('bank-book')->query(auth()->user(), ['from' => '2026-10-24', 'to' => '2026-10-24']);
    expect($report->rows)->toHaveCount(1)->and($report->rows[0]['account'])->toBe($wallet->name);
});
