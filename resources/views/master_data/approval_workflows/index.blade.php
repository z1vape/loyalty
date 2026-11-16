@extends('layouts.app')

@section('title', 'Z1 VapeOS | Approval Workflow')

@section('content')
<div class="space-y-6 max-w-6xl mx-auto pb-12">

    {{-- Breadcrumb & Top Bar --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <a href="{{ route('master-data.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-secondary-container text-on-secondary-container hover:bg-secondary-container/80 transition-colors no-underline">
                    <span class="material-symbols-outlined text-sm">dataset</span>
                    Master Data
                </a>
                <span class="text-xs text-on-surface-variant font-medium">/</span>
                <span class="text-xs text-on-surface-variant font-bold">Approval Workflow</span>
            </div>
            <h1 class="font-headline-lg text-2xl sm:text-3xl font-extrabold text-on-surface">Approval Workflow</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">
                Atur otorisasi peran (Role) yang berhak melakukan persetujuan (approve) transaksi berdasarkan alur status dokumen.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('master-data.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-surface-container-low text-on-surface hover:bg-surface-container-high transition-all border border-outline-variant/50 no-underline shadow-2xs">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Kembali ke Master Data</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span class="text-xs sm:text-sm font-semibold">{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-rose-600">error</span>
            <span class="text-xs sm:text-sm font-semibold">{{ session('error') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
    @endif

    {{-- System Notes Banner --}}
    <div class="bg-blue-50/70 border border-blue-200/80 rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 shadow-2xs">
        <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
            <span class="material-symbols-outlined text-xl">info</span>
        </div>
        <div class="text-xs text-blue-900 leading-relaxed">
            <span class="font-bold block text-sm text-blue-950 mb-1">Mekanisme Otorisasi Status Approval:</span>
            <ul class="list-disc pl-4 space-y-1 text-blue-800">
                <li><strong>Super Admin</strong> selalu memiliki akses otorisasi penuh pada semua proses approval sebagai otoritas sistem tertinggi.</li>
                <li>Pilih peran (Role) mana saja yang diizinkan untuk menyetujui transaksi dari status asal (From) menuju status target (To).</li>
                <li>Pengguna dengan peran yang dipilih akan melihat tombol tindakan <strong>Setujui / Approve</strong> pada halaman detail transaksi terkait.</li>
            </ul>
        </div>
    </div>

    {{-- Workflow Configuration Form --}}
    <form action="{{ route('master-data.approval-workflows.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        @foreach($workflows as $wf)
        <div class="bg-surface-container-lowest border border-outline-variant/80 rounded-2xl overflow-hidden shadow-xs hover:border-primary/40 transition-all">
            {{-- Workflow Card Header --}}
            <div class="p-5 sm:p-6 bg-surface-container-low/40 border-b border-outline-variant/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl {{ $wf->module === 'purchase_request' ? 'bg-amber-500/10 text-amber-700' : 'bg-emerald-500/10 text-[#006c49]' }} flex items-center justify-center shrink-0 shadow-2xs">
                        <span class="material-symbols-outlined text-2xl">
                            {{ $wf->module === 'purchase_request' ? 'assignment_turned_in' : 'local_shipping' }}
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-black text-on-surface">{{ $wf->module_name }}</h2>
                            @if($wf->is_active)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-500 border border-slate-200">Nonaktif</span>
                            @endif
                        </div>
                        <p class="text-xs text-on-surface-variant mt-0.5">{{ $wf->description }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-xs font-bold text-on-surface-variant cursor-pointer">
                        <input type="checkbox" name="workflows[{{ $wf->id }}][is_active]" value="1" {{ $wf->is_active ? 'checked' : '' }} class="rounded border-outline-variant text-[#006c49] focus:ring-[#006c49]/30">
                        <span>Aktifkan Alur Ini</span>
                    </label>
                </div>
            </div>

            {{-- Flow Diagram Visualizer --}}
            <div class="px-5 sm:px-6 pt-5 pb-4 bg-surface-container-lowest border-b border-outline-variant/40">
                <div class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-2.5 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[15px] text-primary">route</span>
                    Tahapan Alur Status Transaksi
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:gap-3 py-2">
                    {{-- Status Awal --}}
                    <div class="px-3.5 py-2 rounded-xl bg-slate-100 text-slate-700 border border-slate-200/80 text-xs font-bold flex items-center gap-2 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        <span>{{ strtoupper($wf->status_from) }} (Pengajuan Awal)</span>
                    </div>

                    {{-- Panah Transisi --}}
                    <div class="flex items-center text-primary font-black px-1">
                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </div>

                    {{-- Status Target (Approval) --}}
                    <div class="px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 text-xs font-extrabold flex items-center gap-2 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ strtoupper($wf->status_to) }} (Persetujuan / Approve)</span>
                    </div>

                    <span class="text-xs text-on-surface-variant italic ml-1">
                        &larr; Diotorisasi oleh role terpilih di bawah ini
                    </span>
                </div>
            </div>

            {{-- Role Selection Section --}}
            <div class="p-5 sm:p-6 bg-surface-container-lowest">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-xs font-bold text-on-surface uppercase tracking-wider">
                        Pilih Role Yang Berhak Menyetujui (Approve):
                    </label>
                    <span class="text-[11px] text-on-surface-variant">Centang satu atau beberapa role</span>
                </div>

                @php
                    $selectedRoleIds = $wf->role_ids ?? [];
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
                    @foreach($roles as $role)
                    @php
                        $isChecked = in_array($role->id, $selectedRoleIds);
                        $isSuperAdminRole = strtolower($role->name) === 'super admin';
                    @endphp
                    <label class="relative flex items-center p-3.5 rounded-xl border {{ $isChecked ? 'border-[#006c49]/60 bg-emerald-50/40 shadow-xs' : 'border-outline-variant/60 bg-surface-container-lowest hover:bg-surface-container-low/50' }} transition-all cursor-pointer group">
                        <input 
                            type="checkbox" 
                            name="workflows[{{ $wf->id }}][role_ids][]" 
                            value="{{ $role->id }}"
                            {{ $isChecked ? 'checked' : '' }}
                            class="w-4 h-4 text-[#006c49] border-outline-variant rounded focus:ring-[#006c49]/30 transition-all"
                        >
                        <div class="ml-3 flex flex-col">
                            <span class="text-xs font-extrabold text-on-surface group-hover:text-primary transition-colors flex items-center gap-1.5">
                                {{ $role->name }}
                                @if($isSuperAdminRole)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-purple-100 text-purple-700 border border-purple-200">Default Root</span>
                                @endif
                            </span>
                            <span class="text-[11px] text-on-surface-variant line-clamp-1 mt-0.5">
                                {{ $role->description ?: 'Pengguna dengan peran ' . $role->name }}
                            </span>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach

        {{-- Action Bar --}}
        <div class="sticky bottom-4 z-10 bg-surface-container-lowest/95 backdrop-blur-md border border-outline-variant/80 rounded-2xl p-4 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-on-surface-variant text-center sm:text-left">
                Perubahan pada alur persetujuan langsung berlaku untuk seluruh transaksi baru maupun transaksi berjalan.
            </div>
            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                <a href="{{ route('master-data.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors border border-outline-variant/50 no-underline text-center">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-[#006c49] hover:bg-[#005a3c] text-white text-xs font-extrabold shadow-sm hover:shadow transition-all cursor-pointer w-full sm:w-auto">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    <span>Simpan Pengaturan Workflow</span>
                </button>
            </div>
        </div>
    </form>

</div>
@endsection

