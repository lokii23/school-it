@php
    $fmt    = fn ($n) => \App\Support\Grading::fmt($n);
    $fmtAvg = fn ($n) => \App\Support\Grading::fmtAvg($n);
    $logo   = asset('images/ccs-logo.png');

    $avgs     = $grades->map(fn ($g) => $g->average)->filter(fn ($a) => $a !== null);
    $classAvg = $avgs->isNotEmpty() ? round($avgs->avg(), \App\Support\Grading::decimals()) : null;
    $passed   = $grades->filter(fn ($g) => $g->status === 'passed')->count();
    $failed   = $grades->filter(fn ($g) => $g->status === 'failed')->count();
    $incs     = $grades->filter(fn ($g) => $g->status === 'inc')->count();
    $labels   = ['passed' => 'Passed', 'failed' => 'Failed', 'inc' => 'INC', 'progress' => 'In progress', 'empty' => ''];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Class record · {{ $section->name }} · {{ $subject }}</title>
    <link rel="icon" type="image/png" href="{{ $logo }}">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        table.rec th, table.rec td { border: 1px solid #94a3b8; padding: 5px 8px; }
        table.rec th { background: #0a1f55; color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .sheet { box-shadow: none !important; margin: 0 !important; max-width: none !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-900 antialiased">

<div class="no-print sticky top-0 z-10 flex items-center justify-between border-b-2 border-[#f6c945] bg-white px-6 py-3 shadow">
    <a href="{{ route('grades.index', ['section_id' => $section->id, 'subject' => $subject]) }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">← Back to grade sheet</a>
    <button onclick="window.print()" class="rounded-xl bg-[#0a1f55] px-6 py-2 text-sm font-semibold text-white hover:bg-[#12307f]">🖨 Print</button>
</div>

<div class="sheet mx-auto my-6 max-w-6xl bg-white p-10 shadow">

    <div class="flex items-center gap-4 border-b-2 border-[#0a1f55] pb-4">
        <img src="{{ $logo }}" alt="Logo" class="h-16 w-16 object-contain">
        <div class="flex-1 text-center">
            <p class="text-lg font-extrabold tracking-wide text-[#0a1f55]">PROFESSIONAL ACADEMY OF THE PHILIPPINES</p>
            <p class="text-sm font-semibold text-[#7a1230]">College of Computer Studies</p>
            <p class="mt-1 text-sm font-bold uppercase tracking-widest">Class Record</p>
        </div>
        <div class="w-16"></div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-1 text-sm sm:grid-cols-4">
        <p><span class="text-slate-500">Section:</span> <strong>{{ $section->name }}</strong></p>
        <p><span class="text-slate-500">Subject:</span> <strong>{{ $subject }}</strong></p>
        <p><span class="text-slate-500">Instructor:</span> <strong>{{ $teacher ?? '—' }}</strong></p>
        <p class="sm:text-right"><span class="text-slate-500">Printed:</span> <strong>{{ now()->format('M d, Y') }}</strong></p>
    </div>

    <table class="rec mt-4 w-full text-sm">
        <thead>
            <tr>
                <th class="w-10 text-center">#</th>
                <th class="text-left">Student</th>
                @foreach ($periods as $label) <th class="text-center">{{ $label }}</th> @endforeach
                <th class="text-center">Average</th>
                <th class="text-left">Status</th>
                <th class="text-left">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $i => $s)
                @php $g = $grades->get($s->id); @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="font-medium">{{ $s->list_name }}@if ($s->is_irregular) <span class="text-[10px] font-normal text-slate-500">(Irregular)</span>@endif</td>
                    @foreach ($periods as $key => $label)
                        <td class="text-center tabular-nums">{{ $g ? ($g->isInc($key) ? 'INC' : $fmt($g->{$key})) : '—' }}</td>
                    @endforeach
                    <td class="text-center font-bold tabular-nums">{{ $g ? ($g->has_inc ? 'INC' : $fmtAvg($g->average)) : '—' }}</td>
                    <td>{{ $labels[$g?->status ?? 'empty'] }}</td>
                    <td class="text-xs">{{ $g->display_remarks ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4 flex flex-wrap gap-x-8 gap-y-1 text-sm">
        <p>Students: <strong>{{ $students->count() }}</strong></p>
        <p>Class average: <strong>{{ $fmtAvg($classAvg) }}</strong></p>
        <p>Passed: <strong>{{ $passed }}</strong></p>
        <p>Failed: <strong>{{ $failed }}</strong></p>
        <p>INC: <strong>{{ $incs }}</strong></p>
        @if ($grades->contains(fn ($g) => ! $g->locked))
            <p class="text-[#7a1230]">* Includes grades not yet finalized.</p>
        @endif
    </div>
    <p class="mt-2 text-xs text-slate-500">Grading: 1.00 is the highest. 3.00 is the lowest passing grade. 3.01 to 4.00 is failed. INC = Incomplete.</p>

    <div class="mt-14 grid grid-cols-2 gap-16 text-center text-sm">
        <div>
            <p class="mb-1 min-h-[1.25rem] font-bold uppercase tracking-wide text-[#0a1f55]">{{ $teacher }}</p>
            <div class="border-t border-slate-700 pt-1 font-medium">Prepared by (Instructor)</div>
        </div>
        <div>
            <div class="mb-1 min-h-[1.25rem] space-y-0.5 font-bold uppercase tracking-wide text-[#0a1f55]">BENJIE S. POLO </div>
            <div class="border-t border-slate-700 pt-1 font-medium">Noted by (OIC/Dean)</div>
        </div>
    </div>
</div>
</body>
</html>