<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\TreasuryTransactionType;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVoucher extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'voucher_date',
            'due_date',
            'contract_date',
        ];
    }


    protected $fillable = [
        'company_id',
        'voucher_number',
        'voucher_type',
        'voucher_date',
        'amount',
        'offer_id',
        'customer_id',
        'payment_method',
        'delivered_by',
        'received_by',
        'check_number',
        'bank_name',
        'due_date',
        'transfer_bank_name',
        'account_number',
        'reference_number',
        'first_party_name',
        'second_party_name',
        'contract_number',
        'contract_date',
        'contract_subject',
        'payment_reason',
        'notes',
        'status',
        'treasury_transaction_id',
        'created_by',
        'metadata',
    ];

    protected $attributes = [
        'status' => 'draft',
        'amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'voucher_date'  => 'date',
            'due_date'      => 'date',
            'contract_date' => 'date',
            'amount'        => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ContractVoucher $voucher) {
            if (blank($voucher->created_by) && auth()->check()) {
                $voucher->created_by = auth()->id();
            }

            if (blank($voucher->voucher_number)) {
                $prefix = match ($voucher->voucher_type) {
                    'payment' => 'PAY',
                    'request' => 'REQ',
                    default   => 'REC',
                };

                $voucher->voucher_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'voucher_number',
                    prefix: $prefix,
                    companyId: $voucher->company_id,
                    padLength: 4,
                    datePart: 'Ymd',
                );
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * اعتماد السند وترحيل الحركة إلى الخزينة
     */
    public function approveAndPostToTreasury(): bool
    {
        if ($this->status === 'approved' && $this->treasury_transaction_id) {
            return false;
        }

        if (in_array($this->voucher_type, ['receipt', 'payment']) && (float) $this->amount > 0) {
            $isReceipt = $this->voucher_type === 'receipt';

            $payerOrRecipient = $isReceipt
                ? ($this->delivered_by ?: ($this->customer?->name ?: $this->first_party_name))
                : ($this->received_by ?: ($this->customer?->name ?: $this->second_party_name));

            $desc = $this->contract_subject
                ?: ($this->offer
                    ? ($isReceipt ? __('contract_vouchers.types.receipt') : __('contract_vouchers.types.payment')) . ' #' . $this->offer->offer_number
                    : ($isReceipt ? __('contract_vouchers.types.receipt') : __('contract_vouchers.types.payment')));

            $collectionItem = PaymentCollectionItem::firstOrCreate(
                [
                    'company_id' => $this->company_id,
                    'main_item'  => \App\Enums\PaymentItemCategory::INVOICES,
                ],
                [
                    'sub_item'      => __('contract_vouchers.title'),
                    'service_price' => 0,
                ]
            );

            $transaction = TreasuryTransaction::create([
                'company_id'                 => $this->company_id,
                'voucher_number'             => $this->voucher_number,
                'transaction_date'           => $this->voucher_date ?? now(),
                'transaction_type'           => $isReceipt ? TreasuryTransactionType::INCOMING : TreasuryTransactionType::OUTGOING,
                'payment_collection_item_id' => $collectionItem->id,
                'recipient_or_payer'         => $payerOrRecipient ?: '—',
                'amount'                     => $this->amount,
                'description'                => $desc,
                'executed_by'                => auth()->user()?->employee?->id,
            ]);

            $this->treasury_transaction_id = $transaction->id;
        }

        $this->status = 'approved';
        $this->save();

        return true;
    }
}
