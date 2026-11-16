@extends('layouts.app')

@section('title', 'Z1 VapeOS | Input Jurnal Manual')

@push('styles')
<style>
    /* Styling for account combobox inside table */
    .journal-row {
        position: relative;
    }
    .journal-row:focus-within {
        z-index: 30;
    }
    .account-select-cell .ts-wrapper {
        width: 100%;
    }
    .account-select-cell .ts-control {
        border-radius: 0.75rem !important;
        font-size: 0.75rem !important;
        min-height: 38px !important;
        height: 38px !important;
        background-color: #eff4ff !important;
        border-color: #c6c6cd !important;
        display: flex !important;
        align-items: center !important;
    }
    .account-select-cell .ts-dropdown {
        min-width: 320px !important;
        z-index: 100 !important;
    }
    .account-select-cell .ts-dropdown .option {
        padding: 0.45rem 0.65rem !important;
    }
</style>
@endpush

@section('content')

<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('accounting.journals.index') }}" class="p-2 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high rounded-xl transition-all">
            <span class="material-symbols-outlined text-xl">arrow_back</span>
        </a>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Input Jurnal Manual</h1>
            <p class="text-on-surface-variant text-body-lg">Catat entri penyesuaian akuntansi, setoran modal, prive, atau koreksi pembukuan.</p>
        </div>
    </div>

    @if($errors->any())
    <div class="p-4 mb-4 bg-error-container text-on-error-container rounded-2xl text-xs flex items-center gap-2">
        <span class="material-symbols-outlined text-base shrink-0">error</span>
        <span>{{ $errors->first() }}</span>
    </div>
    @endif

    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm">
        <form method="POST" action="{{ route('accounting.journals.store') }}" id="journal-form" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Tanggal Entri <span class="text-error">*</span></label>
                    <input type="date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Nomor Referensi (Opsional)</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="Contoh: BUKTI-001" class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono font-bold focus:outline-none focus:ring-1 focus:ring-secondary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface mb-1">Keterangan Jurnal <span class="text-error">*</span></label>
                    <input type="text" name="description" value="{{ old('description') }}" placeholder="Contoh: Penyesuaian kas awal bulan" required class="w-full px-3.5 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
                </div>
            </div>

            {{-- Journal Lines Table --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-on-surface">Rincian Akun (Debit & Kredit) <span class="text-error">*</span></label>
                    <button type="button" id="add-row-btn" class="inline-flex items-center gap-1 px-3 py-1 bg-surface-container-high text-on-surface text-xs font-bold rounded-lg hover:bg-surface-container transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-sm">add</span>
                        Tambah Baris
                    </button>
                </div>

                <div class="border border-outline-variant rounded-xl overflow-visible">
                    <table class="w-full text-xs" id="items-table">
                        <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant">
                            <tr>
                                <th class="px-3 py-2.5 text-left w-72 sm:w-80">Akun</th>
                                <th class="px-3 py-2.5 text-left">Memo / Catatan</th>
                                <th class="px-3 py-2.5 text-right w-36">Debit (Rp)</th>
                                <th class="px-3 py-2.5 text-right w-36">Kredit (Rp)</th>
                                <th class="px-3 py-2.5 text-center w-12">Hapus</th>
                            </tr>
                        </thead>
                        <tbody id="journal-rows-container" class="divide-y divide-outline-variant/60">
                            @php
                                $oldItems = old('items', [
                                    ['account_id' => '', 'memo' => '', 'debit' => '', 'credit' => ''],
                                    ['account_id' => '', 'memo' => '', 'debit' => '', 'credit' => ''],
                                ]);
                            @endphp
                            @foreach($oldItems as $idx => $item)
                            <tr class="journal-row hover:bg-surface-container-low/40">
                                <td class="p-2 account-select-cell">
                                    <select name="items[{{ $idx }}][account_id]" class="account-select w-full" placeholder="-- Pilih Akun --">
                                        <option value="">-- Pilih Akun --</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}" {{ (string)$acc->id === (string)($item['account_id'] ?? '') ? 'selected' : '' }}>
                                                {{ $acc->code }} - {{ $acc->name }} ({{ $acc->type }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <input type="text" name="items[{{ $idx }}][memo]" value="{{ $item['memo'] ?? '' }}" placeholder="Memo baris..." class="w-full h-[38px] px-2.5 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-secondary">
                                </td>
                                <td class="p-2">
                                    <input type="number" step="any" min="0" name="items[{{ $idx }}][debit]" value="{{ $item['debit'] ?? '' }}" placeholder="0" class="debit-input w-full h-[38px] px-2.5 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-right font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
                                </td>
                                <td class="p-2">
                                    <input type="number" step="any" min="0" name="items[{{ $idx }}][credit]" value="{{ $item['credit'] ?? '' }}" placeholder="0" class="credit-input w-full h-[38px] px-2.5 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-right font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
                                </td>
                                <td class="p-2 text-center">
                                    <button type="button" class="remove-row-btn p-1.5 text-on-surface-variant hover:text-error rounded-lg transition-all cursor-pointer">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-surface-container-low font-bold border-t border-outline-variant">
                            <tr>
                                <td colspan="2" class="px-3 py-2.5 text-right text-on-surface-variant text-[11px] uppercase tracking-wider">Total</td>
                                <td class="px-3 py-2.5 text-right font-mono text-xs text-emerald-800" id="total-debit-display">Rp 0</td>
                                <td class="px-3 py-2.5 text-right font-mono text-xs text-sky-800" id="total-credit-display">Rp 0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Balance Status Bar --}}
            <div id="balance-alert" class="p-3.5 rounded-xl border flex items-center justify-between text-xs font-bold transition-all bg-emerald-500/10 border-emerald-500/20 text-emerald-800">
                <div class="flex items-center gap-2" id="balance-status-text">
                    <span class="material-symbols-outlined text-base" id="balance-icon">check_circle</span>
                    <span id="balance-message">Jurnal Seimbang (Balance)</span>
                </div>
                <div class="font-mono" id="balance-diff">Selisih: Rp 0</div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-outline-variant">
                <a href="{{ route('accounting.journals.index') }}" class="px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface text-xs font-bold rounded-xl hover:bg-surface-container-high transition-all no-underline">
                    Batal
                </a>
                <button type="submit" id="submit-btn" class="px-5 py-2.5 bg-primary text-on-primary text-xs font-bold rounded-xl hover:opacity-90 active:scale-95 transition-all cursor-pointer shadow-sm">
                    Simpan Jurnal
                </button>
            </div>
        </form>
    </div>
</div>

<template id="row-template">
    <tr class="journal-row hover:bg-surface-container-low/40">
        <td class="p-2 account-select-cell">
            <select name="items[INDEX][account_id]" class="account-select w-full" placeholder="-- Pilih Akun --">
                <option value="">-- Pilih Akun --</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ $acc->type }})</option>
                @endforeach
            </select>
        </td>
        <td class="p-2">
            <input type="text" name="items[INDEX][memo]" placeholder="Memo baris..." class="w-full h-[38px] px-2.5 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-secondary">
        </td>
        <td class="p-2">
            <input type="number" step="any" min="0" name="items[INDEX][debit]" placeholder="0" class="debit-input w-full h-[38px] px-2.5 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-right font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
        </td>
        <td class="p-2">
            <input type="number" step="any" min="0" name="items[INDEX][credit]" placeholder="0" class="credit-input w-full h-[38px] px-2.5 py-1.5 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-mono text-right font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
        </td>
        <td class="p-2 text-center">
            <button type="button" class="remove-row-btn p-1.5 text-on-surface-variant hover:text-error rounded-lg transition-all cursor-pointer">
                <span class="material-symbols-outlined text-base">delete</span>
            </button>
        </td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = {{ count($oldItems) }};
    const container = document.getElementById('journal-rows-container');
    const addBtn = document.getElementById('add-row-btn');
    const template = document.getElementById('row-template').innerHTML;
    const totalDebitDisplay = document.getElementById('total-debit-display');
    const totalCreditDisplay = document.getElementById('total-credit-display');
    const balanceAlert = document.getElementById('balance-alert');
    const balanceMsg = document.getElementById('balance-message');
    const balanceIcon = document.getElementById('balance-icon');
    const balanceDiff = document.getElementById('balance-diff');
    const submitBtn = document.getElementById('submit-btn');
    const form = document.getElementById('journal-form');

    function initAccountSelect(selectElement) {
        if (!selectElement || selectElement.tomselect) return;

        new TomSelect(selectElement, {
            create: false,
            allowEmptyOption: true,
            placeholder: '-- Pilih Akun --',
            maxOptions: 500,
            sortField: {
                field: 'text',
                direction: 'asc'
            },
            render: {
                option: function(data, escape) {
                    const text = data.text || '';
                    const parts = text.split(' - ');
                    const code = parts[0] || '';
                    const rest = parts.slice(1).join(' - ');

                    const typeMatch = rest.match(/\(([^)]+)\)$/);
                    const type = typeMatch ? typeMatch[1] : '';
                    const name = typeMatch ? rest.replace(/\s*\([^)]+\)$/, '') : rest;

                    return `
                        <div class="py-1 px-1.5 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <span class="font-mono font-bold text-primary mr-1.5">${escape(code)}</span>
                                <span class="font-medium text-on-surface">${escape(name || text)}</span>
                            </div>
                            ${type ? `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-surface-container-high text-on-surface-variant shrink-0">${escape(type)}</span>` : ''}
                        </div>
                    `;
                },
                item: function(data, escape) {
                    return `<div class="font-semibold text-on-surface truncate">${escape(data.text)}</div>`;
                }
            }
        });
    }

    // Initialize TomSelect on initial rows
    container.querySelectorAll('.account-select').forEach(select => {
        initAccountSelect(select);
    });

    function calculateTotals() {
        let totalDebit = 0;
        let totalCredit = 0;

        document.querySelectorAll('.debit-input').forEach(input => {
            const val = parseFloat(input.value) || 0;
            totalDebit += val;
        });

        document.querySelectorAll('.credit-input').forEach(input => {
            const val = parseFloat(input.value) || 0;
            totalCredit += val;
        });

        totalDebitDisplay.textContent = 'Rp ' + Math.round(totalDebit).toLocaleString('id-ID');
        totalCreditDisplay.textContent = 'Rp ' + Math.round(totalCredit).toLocaleString('id-ID');

        const diff = Math.abs(totalDebit - totalCredit);
        balanceDiff.textContent = 'Selisih: Rp ' + Math.round(diff).toLocaleString('id-ID');

        if (totalDebit > 0 && diff < 0.01) {
            balanceAlert.className = 'p-3.5 rounded-xl border flex items-center justify-between text-xs font-bold transition-all bg-emerald-500/10 border-emerald-500/20 text-emerald-800';
            balanceMsg.textContent = 'Jurnal Seimbang (Balance)';
            balanceIcon.textContent = 'check_circle';
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            balanceAlert.className = 'p-3.5 rounded-xl border flex items-center justify-between text-xs font-bold transition-all bg-rose-500/10 border-rose-500/20 text-rose-800';
            balanceMsg.textContent = 'Jurnal Belum Seimbang! Total debit harus sama dengan total kredit.';
            balanceIcon.textContent = 'warning';
        }
    }

    container.addEventListener('input', function(e) {
        if (e.target.classList.contains('debit-input') || e.target.classList.contains('credit-input')) {
            calculateTotals();
        }
    });

    container.addEventListener('click', function(e) {
        const btn = e.target.closest('.remove-row-btn');
        if (btn) {
            const rows = container.querySelectorAll('.journal-row');
            if (rows.length <= 2) {
                alert('Jurnal harus memiliki minimal 2 baris!');
                return;
            }
            const row = btn.closest('.journal-row');
            const select = row.querySelector('.account-select');
            if (select && select.tomselect) {
                select.tomselect.destroy();
            }
            row.remove();
            calculateTotals();
        }
    });

    addBtn.addEventListener('click', function() {
        const newHtml = template.replace(/INDEX/g, rowIndex++);
        container.insertAdjacentHTML('beforeend', newHtml);
        const newRow = container.lastElementChild;
        const newSelect = newRow.querySelector('.account-select');
        if (newSelect) {
            initAccountSelect(newSelect);
        }
        calculateTotals();
    });

    form.addEventListener('submit', function(e) {
        let hasEmptyAccount = false;
        container.querySelectorAll('.account-select').forEach(select => {
            if (!select.value) {
                hasEmptyAccount = true;
                if (select.tomselect) {
                    select.tomselect.focus();
                }
            }
        });

        if (hasEmptyAccount) {
            e.preventDefault();
            alert('Silakan pilih akun untuk setiap baris jurnal.');
            return false;
        }
    });

    calculateTotals();
});
</script>

@endsection

