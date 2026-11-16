@extends('layouts.app')

@section('title', 'Z1 VapeOS | Buku Besar (General Ledger)')

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">Buku Besar (General Ledger)</h1>
        <p class="text-on-surface-variant text-body-lg">Rincian mutasi debit, kredit, dan saldo berjalan per akun.</p>
    </div>

    <div class="flex items-center gap-2 self-start sm:self-auto">
        <a href="{{ route('accounting.journals.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low border border-outline-variant text-on-surface font-bold rounded-xl text-xs sm:text-sm hover:bg-surface-container-high transition-all shadow-xs cursor-pointer no-underline">
            <span class="material-symbols-outlined text-base">receipt_long</span>
            Daftar Jurnal
        </a>
    </div>
</div>

{{-- Filter Form --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-4 mb-6 shadow-xs">
    <form method="GET" action="{{ route('accounting.journals.ledger') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="lg:col-span-2">
            <label class="block text-xs font-semibold text-on-surface-variant mb-1">Pilih Akun Rekening</label>
            <select name="account_id" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ ($selectedAccount?->id == $acc->id) ? 'selected' : '' }}>
                        {{ $acc->code }} - {{ $acc->name }} ({{ $acc->type_label }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
        </div>

        <div>
            <label class="block text-xs font-semibold text-on-surface-variant mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-3 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-xs font-medium focus:outline-none focus:ring-1 focus:ring-secondary">
        </div>

        <div class="lg:col-span-4 flex justify-end gap-2 pt-2 border-t border-outline-variant/60">
            <button type="submit" class="px-5 py-2 bg-secondary text-on-secondary text-xs font-bold rounded-xl hover:opacity-90 transition-all cursor-pointer">
                Tampilkan Buku Besar
            </button>
        </div>
    </form>
</div>

@if($selectedAccount)
{{-- Account Summary Header Card --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-5 mb-6 shadow-xs">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/60">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-sm font-bold bg-surface-container-high px-2 py-0.5 rounded text-on-surface">{{ $selectedAccount->code }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md border text-[10px] font-bold {{ $selectedAccount->type_badge_class }}">
                    {{ $selectedAccount->type_label }}
                </span>
                <span class="text-xs text-on-surface-variant font-medium">Grup: {{ $selectedAccount->category }}</span>
            </div>
            <h2 class="text-xl font-headline-md font-extrabold text-on-surface">{{ $selectedAccount->name }}</h2>
            @if($selectedAccount->description)
                <p class="text-xs text-on-surface-variant mt-0.5">{{ $selectedAccount->description }}</p>
            @endif
        </div>

        <div class="flex items-center gap-4 text-right">
            <div>
                <p class="text-[10px] uppercase font-bold text-on-surface-variant tracking-wider">Saldo Normal</p>
                <p class="font-mono text-xs font-extrabold text-on-surface uppercase">{{ $selectedAccount->normal_balance }}</p>
            </div>
            <div class="h-8 border-r border-outline-variant"></div>
            <div>
                <p class="text-[10px] uppercase font-bold text-on-surface-variant tracking-wider">Saldo Akhir</p>
                <p class="font-mono text-base font-extrabold {{ $closingBalance >= 0 ? 'text-emerald-800' : 'text-error' }}">
                    Rp {{ number_format($closingBalance, 0, ',', '.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Metrics --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4">
        <div>
            <p class="text-[10px] uppercase font-bold text-on-surface-variant tracking-wider">Saldo Awal</p>
            <p class="font-mono text-sm font-bold text-on-surface mt-0.5">
                Rp {{ number_format($openingBalance, 0, ',', '.') }}
            </p>
        </div>
        <div>
            <p class="text-[10px] uppercase font-bold text-emerald-700 tracking-wider">Total Debit Periode</p>
            <p class="font-mono text-sm font-bold text-emerald-800 mt-0.5">
                Rp {{ number_format($totalDebitPeriod, 0, ',', '.') }}
            </p>
        </div>
        <div>
            <p class="text-[10px] uppercase font-bold text-sky-700 tracking-wider">Total Kredit Periode</p>
            <p class="font-mono text-sm font-bold text-sky-800 mt-0.5">
                Rp {{ number_format($totalCreditPeriod, 0, ',', '.') }}
            </p>
        </div>
        <div>
            <p class="text-[10px] uppercase font-bold text-on-surface-variant tracking-wider">Saldo Akhir Periode</p>
            <p class="font-mono text-sm font-bold {{ $closingBalance >= 0 ? 'text-primary' : 'text-error' }} mt-0.5">
                Rp {{ number_format($closingBalance, 0, ',', '.') }}
            </p>
        </div>
    </div>
</div>

{{-- Ledger Table --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-xs">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-surface-container-low text-on-surface-variant font-bold border-b border-outline-variant">
                <tr>
                    <th class="px-4 py-3.5">Tanggal</th>
                    <th class="px-4 py-3.5">No. Jurnal & Ref</th>
                    <th class="px-4 py-3.5">Sumber</th>
                    <th class="px-4 py-3.5">Keterangan / Memo</th>
                    <th class="px-4 py-3.5 text-right w-36">Debit</th>
                    <th class="px-4 py-3.5 text-right w-36">Kredit</th>
                    <th class="px-4 py-3.5 text-right w-36">Saldo Berjalan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/60">
                {{-- Opening Balance Row --}}
                <tr class="bg-surface-container-low/40 font-semibold italic text-on-surface-variant">
                    <td class="px-4 py-2.5">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</td>
                    <td class="px-4 py-2.5" colspan="3">SALDO AWAL (Sebelum {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }})</td>
                    <td class="px-4 py-2.5 text-right font-mono">-</td>
                    <td class="px-4 py-2.5 text-right font-mono">-</td>
                    <td class="px-4 py-2.5 text-right font-mono font-bold text-on-surface">
                        Rp {{ number_format($openingBalance, 0, ',', '.') }}
                    </td>
                </tr>

                @forelse($ledgerEntries as $item)
                <tr class="hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-4 py-3 text-on-surface-variant font-medium whitespace-nowrap">
                        {{ $item->journalEntry?->entry_date ? \Carbon\Carbon::parse($item->journalEntry->entry_date)->format('d/m/Y') : '-' }}
                    </td>
                    <td class="px-4 py-3 font-mono">
                        <div class="font-bold text-on-surface">{{ $item->journalEntry?->entry_number ?? '-' }}</div>
                        @if($item->journalEntry?->reference_number)
                            <div class="text-[10px] text-on-surface-variant">Ref: {{ $item->journalEntry->reference_number }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md border text-[10px] font-bold {{ $item->journalEntry?->source_badge_class }}">
                            {{ $item->journalEntry?->source_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-on-surface">{{ $item->journalEntry?->description }}</div>
                        @if($item->memo)
                            <div class="text-[11px] text-on-surface-variant mt-0.5 italic">Memo: {{ $item->memo }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-mono font-medium text-emerald-800 whitespace-nowrap">
                        {{ $item->debit > 0 ? 'Rp ' . number_format($item->debit, 0, ',', '.') : '-' }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono font-medium text-sky-800 whitespace-nowrap">
                        {{ $item->credit > 0 ? 'Rp ' . number_format($item->credit, 0, ',', '.') : '-' }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono font-bold text-on-surface whitespace-nowrap">
                        Rp {{ number_format($item->running_balance, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-on-surface-variant">
                        <p class="font-medium">Tidak ada mutasi jurnal untuk akun ini pada periode yang dipilih.</p>
                    </td>
                </tr>
                @endforelse

                {{-- Closing Summary Row --}}
                <tr class="bg-surface-container-low font-bold border-t border-outline-variant">
                    <td colspan="4" class="px-4 py-3 text-right uppercase text-[11px] text-on-surface-variant">
                        Total Mutasi Periode & Saldo Akhir
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-emerald-800 whitespace-nowrap">
                        Rp {{ number_format($totalDebitPeriod, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-sky-800 whitespace-nowrap">
                        Rp {{ number_format($totalCreditPeriod, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono font-extrabold text-primary whitespace-nowrap">
                        Rp {{ number_format($closingBalance, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
