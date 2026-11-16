@extends('layouts.app')

@section('title', 'My Profile | InvenTrack')

@section('content')
<div class="max-w-4xl mx-auto flex flex-col gap-gutter">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="font-headline-lg text-headline-lg text-on-surface">My Profile</h1>
        <p class="text-on-surface-variant text-body-lg">Manage your account information and personal details.</p>
    </div>

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
        @csrf
        @method('PUT')

        <!-- Profile Overview Card -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-8 shadow-sm flex flex-col sm:flex-row items-center sm:items-start gap-6">
            <!-- Avatar / Photo Upload -->
            <div class="relative group shrink-0">
                <div class="w-28 h-28 rounded-2xl border-2 border-outline-variant bg-surface-container-low overflow-hidden flex items-center justify-center shadow-sm">
                    @if($user->photo)
                        <img id="profile-photo-preview" src="{{ Storage::url($user->photo) }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                    @else
                        <img id="profile-photo-preview" src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=006c49&color=ffffff&size=128" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                    @endif
                </div>
                <label for="photo-input" class="absolute -bottom-2 -right-2 p-2 bg-[#006c49] text-white hover:bg-[#00553a] rounded-xl shadow-md cursor-pointer transition-transform active:scale-95 flex items-center justify-center" title="Change Profile Picture">
                    <span class="material-symbols-outlined text-base">photo_camera</span>
                </label>
                <input type="file" name="photo" id="photo-input" accept="image/*" class="hidden" onchange="previewProfilePhoto(this)" />
            </div>

            <!-- Header Info -->
            <div class="flex-1 text-center sm:text-left space-y-1">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                    <h2 class="text-xl font-bold text-on-surface">{{ $user->name }}</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#eff4ff] text-[#006c49] border border-outline-variant w-fit mx-auto sm:mx-0">
                        {{ $user->roles->first()?->name ?? 'User' }}
                    </span>
                </div>
                <p class="text-sm text-on-surface-variant font-mono">{{ $user->email }}</p>
                <div class="pt-2 flex flex-wrap gap-2 justify-center sm:justify-start text-xs font-semibold text-on-surface-variant">
                    <span class="bg-surface-container-low px-3 py-1 rounded-xl border border-outline-variant/60 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-secondary">badge</span>
                        {{ $user->employee_number ?? 'No Emp #' }}
                    </span>
                    <span class="bg-surface-container-low px-3 py-1 rounded-xl border border-outline-variant/60 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-secondary">work</span>
                        {{ $user->position ?? 'Staff' }}
                    </span>
                    <span class="bg-surface-container-low px-3 py-1 rounded-xl border border-outline-variant/60 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-secondary">calendar_today</span>
                        Joined {{ $user->join_date ? \Carbon\Carbon::parse($user->join_date)->format('d M Y') : '-' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Personal & Account Details Form -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-8 shadow-sm space-y-6">
            <div class="border-b border-outline-variant/60 pb-4">
                <h3 class="text-base font-bold text-on-surface">Personal & Account Details</h3>
                <p class="text-xs text-on-surface-variant mt-0.5">Update your personal profile information</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Full Name <span class="text-error">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="w-full px-4 py-2.5 rounded-xl border border-outline-variant focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition" />
                    @error('name') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Gender <span class="text-error">*</span></label>
                    <input type="hidden" name="gender" id="gender-input" value="{{ old('gender', $user->gender ?? 'Male') }}"/>
                    <div class="flex gap-3">
                        <button type="button" onclick="setProfileGender('Male')" id="gender-btn-Male" class="flex-1 py-2.5 px-4 rounded-xl border transition-all text-sm font-bold text-center">Male</button>
                        <button type="button" onclick="setProfileGender('Female')" id="gender-btn-Female" class="flex-1 py-2.5 px-4 rounded-xl border transition-all text-sm font-bold text-center">Female</button>
                    </div>
                    @error('gender') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="username" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Username <span class="text-error">*</span></label>
                    <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" required class="w-full px-4 py-2.5 rounded-xl border border-outline-variant focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition" />
                    @error('username') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Email <span class="text-error">*</span></label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="w-full px-4 py-2.5 rounded-xl border border-outline-variant focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition" />
                    @error('email') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="mobile_phone" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Mobile Phone <span class="text-error">*</span></label>
                    <input type="text" name="mobile_phone" id="mobile_phone" value="{{ old('mobile_phone', $user->mobile_phone) }}" required class="w-full px-4 py-2.5 rounded-xl border border-outline-variant focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition" />
                    @error('mobile_phone') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        New Password <span class="text-on-surface-variant font-medium lowercase">(leave blank to keep current)</span>
                    </label>
                    <input type="password" name="password" id="password" placeholder="Enter new password" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition" />
                    @error('password') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="address" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Address</label>
                <textarea name="address" id="address" placeholder="Enter address" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition resize-none min-h-[90px]">{{ old('address', $user->address) }}</textarea>
                @error('address') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Work & System Information (Read Only) -->
        <div class="bg-white rounded-2xl border border-outline-variant p-6 sm:p-8 shadow-sm space-y-6">
            <div class="border-b border-outline-variant/60 pb-4">
                <h3 class="text-base font-bold text-on-surface">Work & System Information</h3>
                <p class="text-xs text-on-surface-variant mt-0.5">Assigned store locations, position, and roles</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 bg-surface-container-lowest border border-outline-variant rounded-xl">
                    <span class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Employee No.</span>
                    <span class="block text-sm font-bold text-on-surface mt-1">{{ $user->employee_number ?? '-' }}</span>
                </div>
                <div class="p-4 bg-surface-container-lowest border border-outline-variant rounded-xl">
                    <span class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Position</span>
                    <span class="block text-sm font-bold text-on-surface mt-1">{{ $user->position ?? '-' }}</span>
                </div>
                <div class="p-4 bg-surface-container-lowest border border-outline-variant rounded-xl">
                    <span class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Status</span>
                    <span class="block text-sm font-bold text-on-surface mt-1">{{ $user->status ?? '-' }}</span>
                </div>
                <div class="p-4 bg-surface-container-lowest border border-outline-variant rounded-xl">
                    <span class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Join Date</span>
                    <span class="block text-sm font-bold text-on-surface mt-1">{{ $user->join_date ? \Carbon\Carbon::parse($user->join_date)->format('d M Y') : '-' }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <!-- Assigned Stores -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Assigned Stores</label>
                    <div class="flex flex-wrap gap-2">
                        @forelse($user->stores as $store)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low text-on-surface text-xs font-semibold rounded-xl border border-outline-variant">
                                <span class="material-symbols-outlined text-sm text-secondary">storefront</span>
                                {{ $store->name }}
                            </span>
                        @empty
                            <span class="text-xs text-on-surface-variant italic">No specific stores assigned (All Stores)</span>
                        @endforelse
                    </div>
                </div>

                <!-- Assigned Roles -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">System Roles</label>
                    <div class="flex flex-wrap gap-2">
                        @forelse($user->roles as $role)
                            <span class="inline-flex items-center px-3 py-1.5 bg-[#eff4ff] text-[#006c49] text-xs font-bold rounded-xl border border-outline-variant">
                                {{ $role->name }}
                            </span>
                        @empty
                            <span class="text-xs text-on-surface-variant italic">No roles assigned</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end gap-3">
            <button type="submit" class="px-6 py-2.5 bg-[#006c49] text-white hover:bg-[#00553a] font-bold rounded-xl active:scale-95 duration-150 transition-all text-sm shadow-sm hover:shadow cursor-pointer">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function previewProfilePhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile-photo-preview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function setProfileGender(value) {
        document.getElementById('gender-input').value = value;
        const maleBtn = document.getElementById('gender-btn-Male');
        const femaleBtn = document.getElementById('gender-btn-Female');

        if (value === 'Male') {
            maleBtn.className = "flex-1 py-2.5 px-4 rounded-xl border border-secondary bg-secondary-container text-on-secondary-container font-bold text-sm text-center transition-all cursor-pointer";
            femaleBtn.className = "flex-1 py-2.5 px-4 rounded-xl border border-outline-variant bg-white text-on-surface hover:bg-surface-container-low font-medium text-sm text-center transition-all cursor-pointer";
        } else {
            femaleBtn.className = "flex-1 py-2.5 px-4 rounded-xl border border-secondary bg-secondary-container text-on-secondary-container font-bold text-sm text-center transition-all cursor-pointer";
            maleBtn.className = "flex-1 py-2.5 px-4 rounded-xl border border-outline-variant bg-white text-on-surface hover:bg-surface-container-low font-medium text-sm text-center transition-all cursor-pointer";
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        setProfileGender(document.getElementById('gender-input').value || 'Male');
    });
</script>
@endpush
