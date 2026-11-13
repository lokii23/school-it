<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('section')
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->q, function ($query, $search) {
                $query->where(fn ($w) => $w
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $counts   = User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');
        $sections = Section::orderBy('name')->get();

        return view('users.index', compact('users', 'counts', 'sections'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        User::create([
            'first_name'  => trim($data['first_name']),
            'middle_name' => ! empty($data['middle_name']) ? trim($data['middle_name']) : null,
            'last_name'   => trim($data['last_name']),
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'role'        => $data['role'],
            'section_id'  => $data['role'] === 'student' ? ($data['section_id'] ?? null) : null,
        ]);

        return redirect()->route('users.index')->with('success', 'User account created.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate($this->rules($user));

        // An admin can't change their own role (prevents locking yourself out)
        $role = $user->is($request->user()) ? $user->role : $data['role'];

        $user->fill([
            'first_name'  => trim($data['first_name']),
            'middle_name' => ! empty($data['middle_name']) ? trim($data['middle_name']) : null,
            'last_name'   => trim($data['last_name']),
            'email'       => $data['email'],
            'role'        => $role,
            'section_id'  => ($role === 'student' && ! $user->is_irregular) ? ($data['section_id'] ?? null) : null,
        ]);
        if ($role !== 'student') {
            $user->is_irregular = false;
        }
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User account updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $photo = $user->photo;

        try {
            $user->delete();
        } catch (QueryException $e) {
            return back()->with('error', 'This user has related records (announcements, exams or results) and cannot be deleted.');
        }

        // Remove the photo file only after the account was really deleted
        if ($photo) {
            Storage::disk('public')->delete($photo);
        }

        return redirect()->route('users.index')->with('success', 'User account deleted.');
    }

    private function rules(?User $user = null): array
    {
        return [
            'first_name'  => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role'        => ['required', Rule::in(['admin', 'teacher', 'student'])],
            'section_id'  => ['nullable', 'exists:sections,id'],
            'password'    => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }
}