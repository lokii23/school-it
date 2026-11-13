@extends('layouts.portal')

@section('title', 'My Profile')

@section('content')
@php
    $inputBase = 'w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55]';
    $roleStyle = [
        'admin'   => 'bg-[#7a1230] text-white',
        'teacher' => 'bg-[#f6c945] text-[#0a1f55]',
        'student' => 'bg-white/15 text-white ring-1 ring-white/30',
    ];
    $needsSplit = is_null($user->first_name) && is_null($user->last_name);
    $pwErr = $errors->updatePassword;
@endphp

<div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-3">

    {{-- ============ LEFT: account card ============ --}}
    <aside class="lg:col-span-1">
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70 lg:sticky lg:top-24">

            <div class="relative bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] px-6 pb-14 pt-8 text-center text-white">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[#f6c945]/20 blur-2xl"></div>
                <span class="relative inline-block rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider {{ $roleStyle[$user->role] ?? 'bg-white/15' }}">
                    {{ $user->role }}
                </span>
            </div>

            <div class="-mt-10 px-6 pb-6 text-center">
                <div class="relative z-10 flex justify-center">
                    <x-avatar :user="$user" size="h-20 w-20" text="text-3xl font-extrabold" ring="border-4 border-white shadow-lg" />
                </div>
                <h2 class="mt-3 text-lg font-bold text-[#0a1f55]">{{ $user->name }}</h2>
                <p class="text-sm text-slate-400">{{ $user->email }}</p>

                <dl class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-left text-sm">
                    @if ($user->student_number)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Student ID</dt>
                            <dd class="font-semibold text-slate-700">{{ $user->student_number }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Role</dt>
                        <dd class="font-semibold capitalize text-slate-700">{{ $user->role }}</dd>
                    </div>
                    @if ($user->role === 'student')
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Section</dt>
                            <dd class="font-semibold text-slate-700">{{ $user->section->name ?? 'Not assigned' }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Member since</dt>
                        <dd class="font-semibold text-slate-700">{{ $user->created_at?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Last updated</dt>
                        <dd class="font-semibold text-slate-700">{{ $user->updated_at?->diffForHumans() ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($user->role === 'student')
                    <p class="mt-5 rounded-xl bg-[#fbf8f1] px-4 py-3 text-left text-xs text-slate-500">
                        Your section and role are managed by the admin. Contact your adviser if something looks wrong.
                    </p>
                @endif
            </div>
        </div>
    </aside>

    {{-- ============ RIGHT: forms ============ --}}
    <div class="space-y-6 lg:col-span-2">
        {{-- Profile photo --}}
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70"
            x-data="{
                preview: null,
                ready: false,
                error: '',
                pick(e) {
                    this.error = ''; this.ready = false; this.preview = null;
                    const file = e.target.files[0];
                    if (!file) return;
                    if (!file.type.startsWith('image/')) { this.error = 'Please choose an image file.'; e.target.value = ''; return; }

                    const url = URL.createObjectURL(file);
                    const img = new Image();
                    img.onload = () => {
                        // Center-crop to a square, max 800px
                        const s = Math.min(img.width, img.height);
                        const out = Math.min(s, 800);
                        const canvas = document.createElement('canvas');
                        canvas.width = canvas.height = out;
                        canvas.getContext('2d').drawImage(img, (img.width - s) / 2, (img.height - s) / 2, s, s, 0, 0, out, out);

                        canvas.toBlob((blob) => {
                            const dt = new DataTransfer();
                            dt.items.add(new File([blob], 'avatar.jpg', { type: 'image/jpeg' }));
                            this.$refs.file.files = dt.files;
                            this.preview = URL.createObjectURL(blob);
                            this.ready = true;
                            URL.revokeObjectURL(url);
                        }, 'image/jpeg', 0.9);
                    };
                    img.onerror = () => { this.error = 'That file could not be read as an image. Try a JPG or PNG.'; };
                    img.src = url;
                },
            }">

            <div class="border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <h2 class="font-semibold">Profile photo</h2>
                <p class="text-xs text-slate-300">Shown in the sidebar, the top bar and the user lists.</p>
            </div>

            <div class="flex flex-col items-center gap-6 p-6 sm:flex-row">

                {{-- Preview --}}
                <div class="shrink-0">
                    <template x-if="preview">
                        <img :src="preview" alt="New photo preview" class="h-28 w-28 rounded-full object-cover ring-4 ring-[#f6c945]">
                    </template>
                    <div x-show="!preview">
                        <x-avatar :user="$user" size="h-28 w-28" text="text-4xl font-extrabold" ring="ring-4 ring-[#f6c945]" />
                    </div>
                </div>

                <div class="w-full flex-1 space-y-3">
                    <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf

                        <input type="file" name="photo" x-ref="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick($event)">

                        <div class="flex flex-wrap gap-3">
                            <button type="button" @click="$refs.file.click()"
                                    class="rounded-xl bg-[#0a1f55]/10 px-5 py-2.5 text-sm font-semibold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">
                                Choose photo
                            </button>
                            <button type="submit" x-show="ready" x-transition
                                    class="rounded-xl bg-[#0a1f55] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]">
                                Save photo
                            </button>
                        </div>

                        <p x-show="error" x-text="error" class="text-sm text-[#7a1230]"></p>
                        @error('photo') <p class="text-sm text-[#7a1230]">{{ $message }}</p> @enderror

                        <p class="text-xs text-slate-400">JPG, PNG or WebP, up to 2 MB. Your photo is cropped to a square automatically.</p>
                    </form>

                    @if ($user->photo)
                        <form method="POST" action="{{ route('profile.photo.destroy') }}"
                            onsubmit="return confirm('Remove your profile photo?')">
                            @csrf @method('DELETE')
                            <button class="text-sm font-semibold text-[#7a1230] hover:underline">Remove current photo</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        {{-- Personal information --}}
        <form method="POST" action="{{ route('profile.update') }}"
              x-data="{
                  first: {{ Js::from(old('first_name', $user->first_name ?? $user->name)) }},
                  middle: {{ Js::from(old('middle_name', $user->middle_name ?? '')) }},
                  last: {{ Js::from(old('last_name', $user->last_name ?? '')) }},
                  get full() { return [this.first, this.middle, this.last].map(s => s.trim()).filter(Boolean).join(' '); },
              }"
              class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
            @csrf
            @method('PATCH')

            <div class="border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <h2 class="font-semibold">Personal information</h2>
                <p class="text-xs text-slate-300">Update your name and email address.</p>
            </div>

            <div class="space-y-5 p-6">

                @if ($needsSplit)
                    <div class="rounded-xl border-l-4 border-[#f6c945] bg-[#fbf8f1] px-4 py-3 text-sm text-slate-600">
                        <p class="font-semibold text-[#0a1f55]">Please split your name.</p>
                        <p class="mt-0.5 text-xs">Your full name is currently saved as one field. Enter your first, middle and last name below, then save.</p>
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="mb-1.5 block text-sm font-medium text-slate-700">First name</label>
                        <input id="first_name" type="text" name="first_name" x-model="first" required maxlength="100" autocomplete="given-name"
                               class="{{ $inputBase }} @error('first_name') ring-[#7a1230] @enderror">
                        @error('first_name') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="middle_name" class="mb-1.5 block text-sm font-medium text-slate-700">Middle name <span class="font-normal text-slate-400">(optional)</span></label>
                        <input id="middle_name" type="text" name="middle_name" x-model="middle" maxlength="100" autocomplete="additional-name"
                               class="{{ $inputBase }} @error('middle_name') ring-[#7a1230] @enderror">
                        @error('middle_name') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="last_name" class="mb-1.5 block text-sm font-medium text-slate-700">Last name</label>
                    <input id="last_name" type="text" name="last_name" x-model="last" required maxlength="100" autocomplete="family-name"
                           class="{{ $inputBase }} @error('last_name') ring-[#7a1230] @enderror">
                    @error('last_name') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-xl bg-[#fbf8f1] px-4 py-3">
                    <p class="text-xs text-slate-400">Your name will appear as</p>
                    <p class="font-semibold text-[#0a1f55]" x-text="full || '—'"></p>
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                           class="{{ $inputBase }} @error('email') ring-[#7a1230] @enderror">
                    @error('email') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-xs text-slate-400">You sign in with this email. Use one you can access.</p>
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-100 bg-[#fbf8f1] px-6 py-4">
                <button type="submit"
                        class="rounded-xl bg-[#0a1f55] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]">
                    Save changes
                </button>
            </div>
        </form>

        {{-- Change password --}}
        <form method="POST" action="{{ route('password.update') }}"
              x-data="{
                  show: false, pw: '', confirm: '',
                  get score() {
                      let s = 0;
                      if (this.pw.length >= 8) s++;
                      if (/[A-Z]/.test(this.pw) && /[a-z]/.test(this.pw)) s++;
                      if (/\d/.test(this.pw)) s++;
                      if (/[^A-Za-z0-9]/.test(this.pw)) s++;
                      return s;
                  },
                  get label() { return ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'][this.score]; },
                  get bar() { return ['bg-[#7a1230]', 'bg-[#7a1230]', 'bg-[#f6c945]', 'bg-emerald-400', 'bg-emerald-500'][this.score]; },
              }"
              class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
            @csrf
            @method('PUT')

            <div class="border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <h2 class="font-semibold">Change password</h2>
                <p class="text-xs text-slate-300">Use a long, unique password to keep your account safe.</p>
            </div>

            <div class="space-y-5 p-6">
                <div>
                    <label for="current_password" class="mb-1.5 block text-sm font-medium text-slate-700">Current password</label>
                    <input id="current_password" :type="show ? 'text' : 'password'" name="current_password" required autocomplete="current-password"
                           class="{{ $inputBase }} @if ($pwErr->has('current_password')) ring-[#7a1230] @endif">
                    @if ($pwErr->has('current_password'))
                        <p class="mt-1.5 text-sm text-[#7a1230]">{{ $pwErr->first('current_password') }}</p>
                    @endif
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">New password</label>
                        <input id="password" :type="show ? 'text' : 'password'" name="password" x-model="pw" required autocomplete="new-password"
                               class="{{ $inputBase }} @if ($pwErr->has('password')) ring-[#7a1230] @endif">
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm new password</label>
                        <input id="password_confirmation" :type="show ? 'text' : 'password'" name="password_confirmation" x-model="confirm" required autocomplete="new-password"
                               class="{{ $inputBase }}">
                    </div>
                </div>

                @if ($pwErr->has('password'))
                    <p class="-mt-2 text-sm text-[#7a1230]">{{ $pwErr->first('password') }}</p>
                @endif

                {{-- Strength + match --}}
                <div x-show="pw.length" x-transition>
                    <div class="flex gap-1.5">
                        <template x-for="n in 4" :key="n">
                            <div class="h-1.5 flex-1 rounded-full transition-colors" :class="n <= score ? bar : 'bg-slate-200'"></div>
                        </template>
                    </div>
                    <div class="mt-1.5 flex justify-between text-xs">
                        <span class="font-semibold text-slate-600" x-text="label"></span>
                        <span x-show="confirm.length" :class="pw === confirm ? 'text-emerald-600' : 'text-[#7a1230]'"
                              x-text="pw === confirm ? '✓ Passwords match' : 'Passwords do not match'"></span>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" x-model="show" class="rounded border-slate-300 text-[#0a1f55] focus:ring-[#0a1f55]">
                    Show passwords
                </label>
            </div>

            <div class="flex justify-end border-t border-slate-100 bg-[#fbf8f1] px-6 py-4">
                <button type="submit"
                        class="rounded-xl bg-[#7a1230] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22]">
                    Update password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection