@extends('layouts.app')

@section('title', 'InvenTrack | New Sale')

@section('content')

{{-- Flash Messages --}}
@if($errors->any())
<div class="mb-4 px-4 py-3 rounded-xl bg-error-container text-on-error-container font-semibold text-sm flex items-center gap-2 shadow-sm">
    <span class="material-symbols-outlined text-base">error</span>
    {{ $errors->first() }}
</div>
@endif

<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('sales.index') }}" class="p-2 rounded-xl bg-surface-container hover:bg-surface-container-highest transition-colors text-on-surface">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <div>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">New Sale</h1>
        <p class="text-on-surface-variant text-body-lg">Record a new sales transaction and deduct stock automatically.</p>
    </div>
</div>

<form action="{{ route('sales.store') }}" method="POST" id="sales-form">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: General Info & Items --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- General Info Card --}}
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm">
                <h2 class="text-base font-bold text-on-surface flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-primary">info</span>
                    General Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Sale Date <span class="text-error">*</span>
                        </label>
                        <input type="date" name="sale_date" value="{{ date('Y-m-d') }}" required
                            class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-semibold focus:outline-none focus:border-primary transition" />
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide">
                                Member Loyalty / Customer
                            </label>
                            <button type="button" onclick="openRegisterModal()" class="text-xs font-bold text-primary hover:underline flex items-center gap-0.5">
                                <span class="material-symbols-outlined text-xs">person_add</span> + Member Baru
                            </button>
                        </div>

                        <!-- Scan/Search Input Bar -->
                        <div id="customer-search-box">
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input type="text" id="loyalty_search_input" placeholder="Scan QR / No. WA / Kode (VP-...)"
                                        class="w-full pl-9 pr-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:border-primary transition font-medium"
                                        onkeydown="if(event.key === 'Enter'){ event.preventDefault(); searchCustomer(); }" />
                                    <span class="material-symbols-outlined absolute left-2.5 top-2.5 text-on-surface-variant text-lg">qr_code_scanner</span>
                                </div>
                                <button type="button" onclick="searchCustomer()" class="px-3.5 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl text-xs font-bold transition flex items-center gap-1">
                                    Cari
                                </button>
                            </div>
                            <p class="text-[11px] text-on-surface-variant mt-1">Arahkan scanner kasir ke QR HP pelanggan atau ketik no WA.</p>
                        </div>

                        <!-- Customer Selected Card (Hidden by default) -->
                        <div id="customer-selected-card" class="hidden p-3.5 rounded-xl bg-surface-container border border-outline-variant relative">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-sm" id="customer-avatar-tier">
                                        ★
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-sm text-on-surface" id="selected-customer-name-display"></span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider" id="selected-customer-tier-badge"></span>
                                        </div>
                                        <p class="text-xs text-on-surface-variant font-mono" id="selected-customer-meta"></p>
                                    </div>
                                </div>
                                <button type="button" onclick="resetCustomer()" class="p-1 text-on-surface-variant hover:text-error hover:bg-error-container/20 rounded-lg transition" title="Ganti Member">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                </button>
                            </div>

                            <!-- Loyalty Points & Stamps info -->
                            <div class="grid grid-cols-2 gap-2 mt-3 pt-2.5 border-t border-outline-variant/60 text-xs">
                                <div class="bg-surface-container-lowest p-2 rounded-lg">
                                    <span class="text-[10px] text-on-surface-variant font-medium block">Poin Belanja</span>
                                    <span class="font-bold text-amber-500 font-mono" id="selected-customer-points">0 Poin</span>
                                </div>
                                <div class="bg-surface-container-lowest p-2 rounded-lg">
                                    <span class="text-[10px] text-on-surface-variant font-medium block">Liquid Stamps</span>
                                    <span class="font-bold text-cyan-500 font-mono" id="selected-customer-stamps">0 / 5</span>
                                </div>
                            </div>

                            <!-- Redeem points checkbox option -->
                            <div class="mt-2.5 pt-2 border-t border-outline-variant/60 flex items-center justify-between">
                                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-on-surface">
                                    <input type="checkbox" id="check_redeem_points" onchange="toggleRedeemPoints(this)" class="rounded text-primary focus:ring-0">
                                    <span>Tukar Poin Diskon</span>
                                </label>
                                <span class="text-xs font-bold text-emerald-600 hidden" id="redeem_points_value_display"></span>
                            </div>

                            <!-- WhatsApp Card Link -->
                            <div class="mt-2 text-right">
                                <a id="customer_wa_link" href="#" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:underline">
                                    <span class="material-symbols-outlined text-xs">chat</span> Kirim Link Kartu via WA
                                </a>
                            </div>
                        </div>

                        <!-- Hidden Form Inputs for Backend -->
                        <input type="hidden" name="customer_id" id="selected_customer_id" value="">
                        <input type="hidden" name="customer_name" id="selected_customer_name" value="">
                        <input type="hidden" name="points_redeemed" id="input_points_redeemed" value="0">
                        <input type="hidden" name="points_discount_amount" id="input_points_discount" value="0">
                    </div>
                </div>
            </div>

            {{-- Products Card --}}
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">shopping_cart</span>
                        Products
                    </h2>
                    <button type="button" onclick="addRow()" class="text-sm font-bold text-primary hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add</span> Add Item
                    </button>
                </div>
                
                <div class="overflow-visible">
                    <table class="w-full text-left" id="items-table">
                        <thead class="text-xs text-on-surface-variant uppercase tracking-wide border-b border-outline-variant">
                            <tr>
                                <th class="pb-3 font-semibold w-2/5">Product</th>
                                <th class="pb-3 font-semibold w-1/6">Price</th>
                                <th class="pb-3 font-semibold w-1/6">Qty</th>
                                <th class="pb-3 font-semibold w-1/6">Discount</th>
                                <th class="pb-3 font-semibold text-right">Subtotal</th>
                                <th class="pb-3 font-semibold w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="text-sm divide-y divide-outline-variant">
                            <!-- Rows will be added here -->
                        </tbody>
                    </table>
                </div>
                
                @if($products->isEmpty())
                <div class="mt-4 p-4 bg-error-container text-on-error-container rounded-xl text-sm font-medium">
                    No products available in stock. Please add stock before creating a sale.
                </div>
                @endif
            </div>

        </div>

        {{-- Right Column: Summary & Notes --}}
        <div class="space-y-6">
            
            {{-- Summary Card --}}
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm">
                <h2 class="text-base font-bold text-on-surface flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-primary">receipt_long</span>
                    Order Summary
                </h2>

                <div class="flex justify-between items-center mb-3 text-sm">
                    <span class="text-on-surface-variant">Subtotal</span>
                    <span class="font-semibold text-on-surface" id="summary-subtotal">Rp 0</span>
                </div>
                <div class="flex justify-between items-center mb-2 text-sm text-error">
                    <span>Total Discount Item</span>
                    <span class="font-semibold" id="summary-discount">-Rp 0</span>
                </div>
                <div class="flex justify-between items-center mb-2 text-sm text-indigo-600 hidden" id="summary-tier-discount-row">
                    <span id="summary-tier-discount-label">Diskon Member</span>
                    <span class="font-semibold" id="summary-tier-discount">-Rp 0</span>
                </div>
                <div class="flex justify-between items-center mb-3 text-sm text-emerald-600 hidden" id="summary-points-row">
                    <span>Diskon Poin Member</span>
                    <span class="font-semibold" id="summary-points-discount">-Rp 0</span>
                </div>
                
                <div class="pt-4 border-t border-outline-variant flex justify-between items-center">
                    <span class="font-bold text-on-surface uppercase tracking-wide text-xs">Total Amount</span>
                    <span class="text-xl font-extrabold text-primary" id="summary-total">Rp 0</span>
                </div>

                <div class="mt-2 text-right text-xs text-on-surface-variant hidden" id="summary-points-earned-box">
                    Perolehan: <span class="font-bold text-amber-500 font-mono" id="summary-points-earned">+0 Poin</span>
                </div>
                
                <input type="hidden" name="total_amount" id="input_total_amount" value="0">

                <button type="submit" class="w-full mt-6 py-3 bg-primary text-on-primary rounded-xl text-sm font-bold hover:opacity-90 active:scale-95 transition border-0 cursor-pointer shadow-sm">
                    Confirm Sale
                </button>
            </div>

            {{-- Notes Card --}}
            <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 shadow-sm">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1.5">
                    Notes
                </label>
                <textarea name="notes" rows="4" placeholder="Transaction notes or remarks..."
                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:border-primary transition resize-none"></textarea>
            </div>

        </div>
    </div>
</form>

{{-- MODAL DAFTAR MEMBER BARU (FAST REGISTRATION) --}}
<div id="registerModal" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl w-full max-w-md p-6 shadow-2xl relative animate-in fade-in zoom-in duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant mb-4">
            <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">person_add</span>
                Daftar Member Loyalty Baru
            </h3>
            <button type="button" onclick="closeRegisterModal()" class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form id="fast-register-form" onsubmit="submitFastRegister(event)" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1">
                    Nama Lengkap <span class="text-error">*</span>
                </label>
                <input type="text" id="reg_name" required placeholder="Contoh: Andi Pratama"
                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:border-primary transition font-medium" />
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide mb-1">
                    Nomor WhatsApp <span class="text-error">*</span>
                </label>
                <input type="tel" id="reg_phone" required placeholder="Contoh: 081234567890"
                    class="w-full px-4 py-2.5 bg-surface-container-low border border-outline-variant rounded-xl text-sm focus:outline-none focus:border-primary transition font-medium" />
                <p class="text-[11px] text-on-surface-variant mt-1">Kartu digital member akan dikirimkan ke nomor ini.</p>
            </div>

            <div class="p-3 rounded-xl bg-primary/10 border border-primary/20 text-xs text-primary font-medium flex items-center gap-2">
                <span class="material-symbols-outlined text-base">card_giftcard</span>
                <span>Member baru langsung mendapatkan bonus <strong>50 Poin</strong>!</span>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeRegisterModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-surface-container transition">
                    Batal
                </button>
                <button type="submit" id="btn-submit-register" class="px-5 py-2.5 bg-primary text-on-primary rounded-xl text-sm font-bold shadow-md hover:opacity-90 transition">
                    Daftar & Pilih Member
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Products & Category Rules JS Data --}}
<script>
    const productsData = @json($products);
    const categoryPointRules = @json($categoryPointRules ?? []);
</script>

@endsection

@push('scripts')
<script>
    let rowCount = 0;
    let activeCustomer = null;
    let isRedeemingPoints = false;
    
    function formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
    }

    function calculateTotals() {
        let grandSubtotal = 0;
        let totalDiscount = 0;
        let totalRawPointsEarned = 0;
        
        const rows = document.querySelectorAll('.item-row');
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const discount = parseFloat(row.querySelector('.item-discount').value) || 0;
            const productId = row.querySelector('.product-id-input')?.value;
            
            const subtotal = (qty * price) - discount;
            const finalSubtotal = subtotal > 0 ? subtotal : 0;
            
            row.querySelector('.item-subtotal-display').innerText = formatCurrency(finalSubtotal);
            row.querySelector('.item-subtotal-input').value = finalSubtotal;
            
            grandSubtotal += (qty * price);
            totalDiscount += discount;

            // Calculate category points for this item (Fixed points per item)
            if (productId && activeCustomer && qty > 0) {
                const prod = productsData.find(p => p.id == productId);
                if (prod) {
                    const catName = (prod.category || '').toLowerCase().trim();
                    let ptsPerItem = 1;
                    if (categoryPointRules) {
                        for (const [k, v] of Object.entries(categoryPointRules)) {
                            if (k.toLowerCase().trim() === catName) {
                                ptsPerItem = parseInt(v) || 0;
                                break;
                            }
                        }
                    }
                    totalRawPointsEarned += (ptsPerItem * qty);
                }
            }
        });
        
        let netBeforeTierDiscount = grandSubtotal - totalDiscount;
        if (netBeforeTierDiscount < 0) netBeforeTierDiscount = 0;

        // Member Tier Discount (Potongan Langsung Belanja Berdasarkan Tier)
        let tierDiscountAmount = 0;
        const tierDiscountRow = document.getElementById('summary-tier-discount-row');
        if (activeCustomer && activeCustomer.tier_discount_percent > 0) {
            tierDiscountAmount = Math.round(netBeforeTierDiscount * (activeCustomer.tier_discount_percent / 100));
            if (tierDiscountRow) {
                tierDiscountRow.classList.remove('hidden');
                document.getElementById('summary-tier-discount-label').innerText = `Diskon Member ${activeCustomer.tier} (${activeCustomer.tier_discount_percent}%)`;
                document.getElementById('summary-tier-discount').innerText = '-' + formatCurrency(tierDiscountAmount);
            }
        } else {
            if (tierDiscountRow) tierDiscountRow.classList.add('hidden');
        }

        let grandTotal = netBeforeTierDiscount - tierDiscountAmount;
        if (grandTotal < 0) grandTotal = 0;

        // Points Discount Calculation
        let pointsDiscountAmount = 0;
        let pointsRedeemed = 0;

        if (activeCustomer && isRedeemingPoints && activeCustomer.points > 0) {
            const maxPointsRp = activeCustomer.points_value_rp; // Rp value of available points based on tier
            const pointRate = (activeCustomer.points > 0) ? (activeCustomer.points_value_rp / activeCustomer.points) : 100;
            pointsDiscountAmount = Math.min(maxPointsRp, grandTotal);
            pointsRedeemed = Math.ceil(pointsDiscountAmount / (pointRate || 100));
            grandTotal -= pointsDiscountAmount;

            document.getElementById('summary-points-row').classList.remove('hidden');
            document.getElementById('summary-points-discount').innerText = '-' + formatCurrency(pointsDiscountAmount);
            document.getElementById('redeem_points_value_display').classList.remove('hidden');
            document.getElementById('redeem_points_value_display').innerText = '-' + formatCurrency(pointsDiscountAmount);
        } else {
            document.getElementById('summary-points-row').classList.add('hidden');
            document.getElementById('redeem_points_value_display').classList.add('hidden');
        }

        document.getElementById('input_points_redeemed').value = pointsRedeemed;
        document.getElementById('input_points_discount').value = pointsDiscountAmount;

        document.getElementById('summary-subtotal').innerText = formatCurrency(grandSubtotal);
        document.getElementById('summary-discount').innerText = '-' + formatCurrency(totalDiscount);
        document.getElementById('summary-total').innerText = formatCurrency(grandTotal > 0 ? grandTotal : 0);
        document.getElementById('input_total_amount').value = grandTotal > 0 ? grandTotal : 0;

        // Estimated Earned Points Box (Based on item categories and tier multiplier)
        const pointsEarnedBox = document.getElementById('summary-points-earned-box');
        if (activeCustomer) {
            const multiplier = activeCustomer.tier_multiplier || 1.0;
            const estPoints = Math.round(totalRawPointsEarned * multiplier);
            pointsEarnedBox.classList.remove('hidden');
            document.getElementById('summary-points-earned').innerText = `+${estPoints} Poin (${multiplier}x)`;
        } else {
            pointsEarnedBox.classList.add('hidden');
        }
    }

    // Customer Loyalty Handlers
    function searchCustomer() {
        const query = document.getElementById('loyalty_search_input').value.trim();
        if (!query) return;

        fetch(`{{ route('loyalty.search') }}?query=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.found) {
                    selectCustomer(data.customer);
                } else {
                    if (confirm('Member tidak ditemukan. Mau daftarkan sebagai member baru sekarang?')) {
                        openRegisterModal(query);
                    }
                }
            })
            .catch(err => {
                console.error(err);
                alert('Gagal mencari member. Silakan coba lagi.');
            });
    }

    function selectCustomer(cust) {
        activeCustomer = cust;
        document.getElementById('selected_customer_id').value = cust.id;
        document.getElementById('selected_customer_name').value = cust.name;

        // Update display card
        document.getElementById('selected-customer-name-display').innerText = cust.name;
        document.getElementById('selected-customer-meta').innerText = `${cust.member_code} • ${cust.phone}`;
        const pointValueRp = cust.points_value_rp ?? (cust.points * 100);
        document.getElementById('selected-customer-points').innerText = `${cust.points.toLocaleString('id-ID')} Poin (Rp ${pointValueRp.toLocaleString('id-ID')})`;
        document.getElementById('selected-customer-stamps').innerText = `${cust.liquid_stamp_count} / 5 Stempel`;

        const badge = document.getElementById('selected-customer-tier-badge');
        badge.innerText = `★ ${cust.tier.toUpperCase()}`;
        const color = cust.tier_badge_color || 'emerald';
        const colorMap = {
            'slate': 'bg-slate-500/15 text-slate-700 border border-slate-500/20',
            'emerald': 'bg-emerald-500/15 text-emerald-600 border border-emerald-500/20',
            'cyan': 'bg-sky-500/15 text-sky-600 border border-sky-500/20',
            'amber': 'bg-amber-500/15 text-amber-600 border border-amber-500/20',
            'purple': 'bg-purple-500/15 text-purple-600 border border-purple-500/20',
            'rose': 'bg-rose-500/15 text-rose-600 border border-rose-500/20',
        };
        badge.className = `text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${colorMap[color] || colorMap['emerald']}`;

        document.getElementById('customer_wa_link').href = cust.whatsapp_url;

        // Toggle visibility
        document.getElementById('customer-search-box').classList.add('hidden');
        document.getElementById('customer-selected-card').classList.remove('hidden');

        // Reset point redemption checkbox
        document.getElementById('check_redeem_points').checked = false;
        isRedeemingPoints = false;

        calculateTotals();
    }

    function resetCustomer() {
        activeCustomer = null;
        isRedeemingPoints = false;
        document.getElementById('selected_customer_id').value = '';
        document.getElementById('selected_customer_name').value = '';
        document.getElementById('check_redeem_points').checked = false;

        document.getElementById('customer-selected-card').classList.add('hidden');
        document.getElementById('customer-search-box').classList.remove('hidden');
        document.getElementById('loyalty_search_input').value = '';
        document.getElementById('loyalty_search_input').focus();

        calculateTotals();
    }

    function toggleRedeemPoints(checkbox) {
        isRedeemingPoints = checkbox.checked;
        calculateTotals();
    }

    // Modal Registration Handlers
    function openRegisterModal(prefillPhoneOrName = '') {
        const modal = document.getElementById('registerModal');
        modal.classList.remove('hidden');
        if (/^[0-9+]+$/.test(prefillPhoneOrName)) {
            document.getElementById('reg_phone').value = prefillPhoneOrName;
            document.getElementById('reg_name').value = '';
            document.getElementById('reg_name').focus();
        } else {
            document.getElementById('reg_name').value = prefillPhoneOrName;
            document.getElementById('reg_phone').value = '';
            document.getElementById('reg_phone').focus();
        }
    }

    function closeRegisterModal() {
        document.getElementById('registerModal').classList.add('hidden');
    }

    function submitFastRegister(event) {
        event.preventDefault();
        const btn = document.getElementById('btn-submit-register');
        btn.disabled = true;
        btn.innerText = 'Menyimpan...';

        const payload = {
            name: document.getElementById('reg_name').value,
            phone: document.getElementById('reg_phone').value,
            _token: '{{ csrf_token() }}'
        };

        fetch(`{{ route('loyalty.register') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = 'Daftar & Pilih Member';
            if (data.success) {
                closeRegisterModal();
                selectCustomer({
                    id: data.customer.id,
                    name: data.customer.name,
                    phone: data.customer.phone,
                    member_code: data.customer.member_code,
                    tier: data.customer.tier,
                    tier_badge_color: data.customer.tier_badge_color || 'emerald',
                    tier_discount_percent: data.customer.tier_discount_percent || 0,
                    tier_multiplier: data.customer.tier_multiplier || 1.0,
                    points: data.customer.points,
                    points_value_rp: data.customer.points_value_rp ?? (data.customer.points * 100),
                    liquid_stamp_count: data.customer.liquid_stamp_count,
                    whatsapp_url: data.whatsapp_url
                });

                if (confirm('Member berhasil dibuat! Mau buka WhatsApp untuk kirim link kartu sekarang?')) {
                    window.open(data.whatsapp_url, '_blank');
                }
            } else {
                alert(data.message || 'Gagal mendaftarkan member.');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = 'Daftar & Pilih Member';
            console.error(err);
            alert('Terjadi kesalahan saat mendaftarkan member.');
        });
    }

    function addRow() {
        const tbody = document.getElementById('items-body');
        const currentId = rowCount;
        
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td class="py-3 pr-2">
                <input type="hidden" name="items[${currentId}][product_id]" class="product-id-input" required>
                
                <div class="product-combobox-wrapper">
                    <button type="button" class="combobox-trigger w-full flex items-center justify-between bg-surface-container-low border border-outline-variant rounded-xl py-2 px-3 text-sm text-on-surface font-medium hover:bg-surface-container-high transition text-left">
                        <span class="combobox-label truncate text-on-surface-variant">Select Product...</span>
                        <span class="material-symbols-outlined text-sm text-on-surface-variant combobox-arrow transition-transform">expand_more</span>
                    </button>
                </div>
            </td>
            <td class="py-3 px-2">
                <input type="number" name="items[${currentId}][selling_price]" required min="0" oninput="calculateTotals()"
                    class="item-price w-full bg-surface-container-low border border-outline-variant rounded-xl py-2 px-3 text-sm focus:outline-none focus:border-primary" />
            </td>
            <td class="py-3 px-2">
                <input type="number" name="items[${currentId}][quantity]" required min="1" value="1" oninput="calculateTotals()"
                    class="item-qty w-full bg-surface-container-low border border-outline-variant rounded-xl py-2 px-3 text-sm focus:outline-none focus:border-primary" />
            </td>
            <td class="py-3 px-2">
                <input type="number" name="items[${currentId}][discount]" min="0" value="0" oninput="calculateTotals()"
                    class="item-discount w-full bg-surface-container-low border border-outline-variant rounded-xl py-2 px-3 text-sm focus:outline-none focus:border-primary" />
            </td>
            <td class="py-3 pl-2 text-right">
                <span class="item-subtotal-display font-bold text-primary">Rp 0</span>
                <input type="hidden" name="items[${currentId}][subtotal]" class="item-subtotal-input" value="0">
            </td>
            <td class="py-3 pl-2 text-right">
                <button type="button" onclick="this.closest('tr').remove(); calculateTotals();" class="p-1.5 text-error hover:bg-error-container rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-sm">delete</span>
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);

        // Build portal dropdown and append to body
        const wrapper = tr.querySelector('.product-combobox-wrapper');
        const trigger = wrapper.querySelector('.combobox-trigger');
        const label = wrapper.querySelector('.combobox-label');
        const arrow = wrapper.querySelector('.combobox-arrow');
        const hiddenInput = tr.querySelector('.product-id-input');
        const priceInput = tr.querySelector('.item-price');
        const qtyInput = tr.querySelector('.item-qty');

        // Create dropdown portal
        const dropdown = document.createElement('div');
        dropdown.className = 'combobox-dropdown-portal fixed bg-surface-container-lowest border border-outline-variant rounded-xl shadow-2xl p-2 z-[9999] hidden';
        dropdown.style.width = '288px';
        dropdown.innerHTML = `
            <input type="text" placeholder="Search product or SKU..." class="combobox-search w-full px-3 py-1.5 text-xs bg-surface-container-low border border-outline-variant rounded-lg mb-1.5 focus:outline-none focus:ring-1 focus:ring-secondary">
            <div class="combobox-options space-y-1">
                ${productsData.map(p => `
                    <div data-id="${p.id}" data-name="${p.name}" data-variant="${p.variant || ''}" data-price="${p.selling_price}" data-stock="${p.stock}" data-sku="${p.sku}"
                         class="combobox-option-item px-3 py-2 text-xs font-semibold rounded-lg cursor-pointer hover:bg-surface-container-high flex justify-between items-center transition-colors">
                        <div class="min-w-0 flex-1 truncate">
                            <div class="text-on-surface truncate">${p.name}${p.variant ? ' (' + p.variant + ')' : ''}</div>
                            <div class="text-[10px] text-on-surface-variant">SKU: ${p.sku}</div>
                        </div>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded ${p.stock > 0 ? 'bg-secondary-container text-on-secondary-container' : 'bg-error-container text-error'} shrink-0 ml-2">
                            Stok: ${p.stock}
                        </span>
                    </div>
                `).join('')}
            </div>
        `;
        document.body.appendChild(dropdown);

        const searchInput = dropdown.querySelector('.combobox-search');

        function positionDropdown() {
            const rect = trigger.getBoundingClientRect();
            dropdown.style.top = (rect.bottom + window.scrollY + 6) + 'px';
            dropdown.style.left = (rect.left + window.scrollX) + 'px';
        }

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            // Close all other portals
            document.querySelectorAll('.combobox-dropdown-portal').forEach(d => {
                if (d !== dropdown) {
                    d.classList.add('hidden');
                }
            });
            document.querySelectorAll('.combobox-arrow').forEach(a => {
                if (a !== arrow) a.style.transform = 'rotate(0deg)';
            });

            const isHidden = dropdown.classList.contains('hidden');
            if (isHidden) {
                positionDropdown();
                dropdown.classList.remove('hidden');
                arrow.style.transform = 'rotate(180deg)';
                searchInput.focus();
            } else {
                dropdown.classList.add('hidden');
                arrow.style.transform = 'rotate(0deg)';
            }
        });

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            dropdown.querySelectorAll('.combobox-option-item').forEach(item => {
                const text = (item.getAttribute('data-name') + ' ' + item.getAttribute('data-sku')).toLowerCase();
                item.style.display = text.includes(query) ? 'flex' : 'none';
            });
        });

        dropdown.querySelectorAll('.combobox-option-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                const pId = item.getAttribute('data-id');
                const pName = item.getAttribute('data-name');
                const pVariant = item.getAttribute('data-variant');
                const pPrice = item.getAttribute('data-price');
                const pStock = item.getAttribute('data-stock');

                hiddenInput.value = pId;
                label.textContent = pName + (pVariant ? ' (' + pVariant + ')' : '');
                label.classList.remove('text-on-surface-variant');
                label.classList.add('text-on-surface', 'font-semibold');

                priceInput.value = pPrice;
                qtyInput.max = pStock;

                dropdown.classList.add('hidden');
                arrow.style.transform = 'rotate(0deg)';

                calculateTotals();
            });
        });

        rowCount++;
    }

    // Add first row on load if we have products
    document.addEventListener('DOMContentLoaded', () => {
        if (productsData.length > 0) {
            addRow();
        }
        
        document.addEventListener('click', () => {
            document.querySelectorAll('.combobox-dropdown-portal').forEach(d => d.classList.add('hidden'));
            document.querySelectorAll('.combobox-arrow').forEach(a => a.style.transform = 'rotate(0deg)');
        });

        window.addEventListener('scroll', () => {
            document.querySelectorAll('.combobox-dropdown-portal').forEach(d => d.classList.add('hidden'));
            document.querySelectorAll('.combobox-arrow').forEach(a => a.style.transform = 'rotate(0deg)');
        }, true);

        document.getElementById('sales-form').addEventListener('submit', function(e) {
            if (document.querySelectorAll('.item-row').length === 0) {
                e.preventDefault();
                alert('Please add at least one product to the sale.');
            }
        });
    });
</script>
@endpush
