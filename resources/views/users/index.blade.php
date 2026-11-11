@extends('layouts.portal')

@section('title', 'Users')

@section('content')
@php
    $roleStyle = [
        'admin'   => 'bg-[#7a1230]/10 text-[#7a1230]',
        'teacher' => 'bg-[#f6c945]/30 text-[#7a5a00]',
        'student' => 'bg-[#0a1f55]/10 text-[#0a1f55]',
    ];
    $inputBase = 'w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55]';
    $tabs = ['' => 'All', 'admin' => 'Admins', 'teacher' => 'Teachers', 'student' => 'Students'];
@endphp

<div
    x-data="{
        open: {{ $errors->any() ? 'true' : 'false' }},
        editId: {{ Js::from(old('_edit_id')) }},
        f: {{ Js::from([
            'first_name'  => old('first_name', ''),
            'middle_name' => old('middle_name', ''),
            'last_name'   => old('last_name', ''),
            'email'       => old('email', ''),
            'role'        => old('role', 'student'),
            'section_id'  => (string) old('section_id', ''),
        ]) }},
        create() {
            this.open = true; this.editId = null;
            this.f = { first_name: '', middle_name: '', last_name: '', email: '', role: 'student', section_id: '' };
        },
        edit(u) { this.open = true; this.editId = u.id; this.f = { ...u }; },
    }"
>

    {{-- Role tabs --}}
    <div class="mb-5 flex flex-wrap gap-2">
        @foreach ($tabs as $value => $label)
            @php
                $isActive = (string) request('role') === (string) $value;
                $count = $value === '' ? $counts->sum() : ($counts[$value] ?? 0);
            @endphp
            <a href="{{ route('users.index', array_filter(['role' => $value, 'q' => request('q')])) }}"
               class="rounded-full px-4 py-1.5 text-sm font-semibold transition
                      {{ $isActive ? 'bg-[#0a1f55] text-white shadow' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                {{ $label }}
                <span class="ml-1 rounded-full px-1.5 text-xs {{ $isActive ? 'bg-[#f6c945] text-[#0a1f55]' : 'bg-slate-100 text-slate-500' }}">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- Toolbar --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="relative w-full sm:max-w-sm">
            @if (request('role')) <input type="hidden" name="role" value="{{ request('role') }}"> @endif
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or email…"
                   class="w-full rounded-xl border-0 bg-white py-2.5 pl-4 pr-20 text-sm shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55]">
            <button class="absolute inset-y-1 right-1 rounded-lg bg-[#0a1f55] px-4 text-xs font-semibold text-white hover:bg-[#12307f]">Search</button>
        </form>

        <button @click="create()"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#7a1230] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22]">
            <span class="text-lg leading-none">+</span> Add user
        </button>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b-2 border-[#f6c945] bg-[#0a1f55] text-xs uppercase tracking-wider text-white">
                    <tr>
                        <th class="px-6 py-3.5 font-semibold">User</th>
                        <th class="px-6 py-3.5 font-semibold">Role</th>
                        <th class="px-6 py-3.5 font-semibold">Section</th>
                        <th class="px-6 py-3.5 font-semibold">Joined</th>
                        <th class="px-6 py-3.5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $u)
                        <tr class="transition hover:bg-[#fbf8f1]">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <x-avatar :user="$u" size="h-10 w-10" />
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-[#0a1f55]">
                                            {{ $u->name }}
                                            @if ($u->is(auth()->user())) <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">You</span> @endif
                                        </p>
                                        <p class="truncate text-xs text-slate-400">{{ $u->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $roleStyle[$u->role] ?? 'bg-slate-100 text-slate-600' }}">{{ $u->role }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $u->section->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-400">{{ $u->created_at?->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button type="button"
                                            @click="edit({{ Js::from([
                                                'id'          => $u->id,
                                                'first_name'  => $u->first_name ?? $u->name,
                                                'middle_name' => $u->middle_name ?? '',
                                                'last_name'   => $u->last_name ?? '',
                                                'email'       => $u->email,
                                                'role'        => $u->role,
                                                'section_id'  => (string) $u->section_id,
                                            ]) }})"
                                            class="rounded-lg bg-[#0a1f55]/10 px-3.5 py-1.5 text-xs font-semibold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">
                                        Edit
                                    </button>

                                    @unless ($u->is(auth()->user()))
                                        <form method="POST" action="{{ route('users.destroy', $u) }}"
                                              onsubmit="return confirm('Delete {{ addslashes($u->name) }}? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button class="rounded-lg bg-[#7a1230]/10 px-3.5 py-1.5 text-xs font-semibold text-[#7a1230] transition hover:bg-[#7a1230] hover:text-white">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center">
                                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">👥</div>
                                <p class="font-semibold text-[#0a1f55]">No users found.</p>
                                <p class="mt-1 text-sm text-slate-400">Try another search or filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>

    {{-- ============ MODAL (add + edit) ============ --}}
    <div x-show="open" x-transition.opacity style="display:none"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-[#071640]/70 p-4 sm:items-center"
         @keydown.escape.window="open = false">
        <div @click.outside="open = false" class="my-8 w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">

            <div class="flex items-center justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <h2 class="text-lg font-semibold" x-text="editId ? 'Edit user' : 'Add user'"></h2>
                <button @click="open = false" class="text-white/70 hover:text-white">✕</button>
            </div>

            <form method="POST"
                  :action="editId ? '{{ url('users') }}/' + editId : '{{ route('users.store') }}'"
                  class="space-y-5 p-6">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!editId">
                <input type="hidden" name="_edit_id" :value="editId">

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">First name</label>
                        <input type="text" name="first_name" x-model="f.first_name" required placeholder="Juan" class="{{ $inputBase }} @error('first_name') ring-[#7a1230] @enderror">
                        @error('first_name') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Middle name <span class="font-normal text-slate-400">(optional)</span></label>
                        <input type="text" name="middle_name" x-model="f.middle_name" placeholder="Santos" class="{{ $inputBase }} @error('middle_name') ring-[#7a1230] @enderror">
                        @error('middle_name') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Last name</label>
                    <input type="text" name="last_name" x-model="f.last_name" required placeholder="Dela Cruz" class="{{ $inputBase }} @error('last_name') ring-[#7a1230] @enderror">
                    @error('last_name') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" x-model="f.email" required placeholder="name@school.edu" class="{{ $inputBase }} @error('email') ring-[#7a1230] @enderror">
                    @error('email') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Role</label>
                        <select name="role" x-model="f.role" class="{{ $inputBase }} @error('role') ring-[#7a1230] @enderror">
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                        @error('role') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    </div>

                    <div x-show="f.role === 'student'">
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Section</label>
                        <select name="section_id" x-model="f.section_id" :disabled="f.role !== 'student'" class="{{ $inputBase }} @error('section_id') ring-[#7a1230] @enderror">
                            <option value="">— Not assigned —</option>
                            @foreach ($sections as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('section_id') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Password <span x-show="editId" class="font-normal text-slate-400">(leave blank to keep)</span>
                        </label>
                        <input type="password" name="password" :required="!editId" autocomplete="new-password" placeholder="••••••••" class="{{ $inputBase }} @error('password') ring-[#7a1230] @enderror">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirm password</label>
                        <input type="password" name="password_confirmation" :required="!editId" autocomplete="new-password" placeholder="••••••••" class="{{ $inputBase }}">
                    </div>
                </div>
                @error('password') <p class="-mt-3 text-sm text-[#7a1230]">{{ $message }}</p> @enderror

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit"
                            class="rounded-xl bg-[#0a1f55] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]"
                            x-text="editId ? 'Save changes' : 'Create user'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection