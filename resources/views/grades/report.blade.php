@php
    $fmtAvg = fn ($n) => \App\Support\Grading::fmtAvg($n);
    $logo   = asset('images/ccs-logo.png');
    $labels = ['passed' => 'Passed', 'failed' => 'Failed', 'progress' => 'In progress', 'empty' => '', 'inc' => 'INC',];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report of Grades</title>
    <link rel="icon" type="image/png" href="{{ $logo }}">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        table.rec th, table.rec td { border: 1px solid #94a3b8; padding: 5px 8px; }
        table.rec th { background: #0a1f55; color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page + .page { margin-top: 1.5rem; }
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .page { box-shadow: none !important; margin: 0 !important; max-width: none !important; padding: 0 !important; page-break-after: always; }
            .page:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-900 antialiased">

<div class="no-print sticky top-0 z-10 flex items-center justify-between border-b-2 border-[#f6c945] bg-white px-6 py-3 shadow">
    <a href="{{ $backUrl }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">← Back</a>
    <div class="flex items-center gap-4">
        <span class="hidden text-xs text-slate-400 sm:block">{{ $students->count() }} {{ Str::plural('report card', $students->count()) }}</span>
        <button onclick="window.print()" class="rounded-xl bg-[#0a1f55] px-6 py-2 text-sm font-semibold text-white hover:bg-[#12307f]">🖨 Print</button>
    </div>
</div>

<div class="my-6">
@foreach ($students as $s)
    @php
        $list    = $grades->get($s->id, collect());
        $avgs    = $list->map(fn ($g) => $g->average)->filter(fn ($a) => $a !== null);
        $general = $avgs->isNotEmpty() ? round($avgs->avg(), \App\Support\Grading::decimals()) : null;
    @endphp

    <div class="page mx-auto max-w-3xl bg-white p-10 shadow">

        <div class="flex items-center gap-4 border-b-2 border-[#0a1f55] pb-4">
            <img src="{{ $logo }}" alt="Logo" class="h-16 w-16 object-contain">
            <div class="flex-1 text-center">
                <p class="text-base font-extrabold tracking-wide " style="font-family: Old English Text MT; color:#BC0202" >Professional Academy of the Philippines</p>
                <p class="text-sm font-semibold text-[#BC0202]">College of Computer Studies</p>
                <p class="mt-1 text-sm font-bold uppercase tracking-widest">Report of Grades</p>
            </div>
            <div class="w-16"></div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
            <p><span class="text-slate-500">Student:</span> <strong>{{ $s->list_name }}</strong></p>
            <p class="text-right"><span class="text-slate-500">Section:</span> <strong>{{ $s->is_irregular ? 'Irregular' : ($s->section->name ?? '—') }}</strong>
            <p><span class="text-slate-500">Student ID:</span> {{ $s->student_number ?? '—' }}</p>
            <p class="text-right"><span class="text-slate-500">Date:</span> {{ now()->format('M d, Y') }}</p>
        </div>

        @if ($list->isEmpty())
            <p class="mt-10 text-center text-slate-400">No grades have been recorded yet.</p>
        @else
            <table class="rec mt-4 w-full text-xs">
                <thead>
                    <tr>
                        <th class="text-left">Subject</th>
                        @foreach ($periods as $label) <th class="text-center">{{ $label }}</th> @endforeach
                        <th class="text-center">Avg.</th>
                        <th class="text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($list as $g)
                        <tr>
                            <td class="font-medium">
                                {{ $g->subject }}
                                @if ($g->display_remarks) <div class="text-[10px] font-normal text-slate-500">{{ $g->display_remarks }}</div> @endif
                            </td>
                            @foreach ($periods as $key => $label)
                                <td class="text-center tabular-nums">{{ $g->isInc($key) ? 'INC' : $fmtAvg($g->{$key}) }}</td>
                            @endforeach
                            <td class="text-center font-bold tabular-nums">{{ $g->has_inc ? 'INC' : $fmtAvg($g->average) }}</td>
                            <td>{{ $labels[$g->status] }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-slate-50 font-bold">
                        <td colspan="{{ count($periods) + 1 }}" class="text-right">General average</td>
                        <td class="text-center tabular-nums">{{ $fmtAvg($general) }}</td>
                        <td>{{ $general === null ? '' : (\App\Support\Grading::passes($general) ? 'Passed' : 'Below passing') }}</td>
                    </tr>
                </tbody>
            </table>

            <p class="mt-3 text-[11px] text-slate-500">
                Grading: 1.00 is the highest. 3.00 is the lowest passing grade. 3.01 to 4.00 is failed.
                @if ($list->contains(fn ($g) => ! $g->locked))
                    <span class="font-semibold text-[#7a1230]">Unofficial copy: includes grades that are not yet finalized.</span>
                @endif
            </p>
        @endif

        <div class="mt-20 grid grid-cols-2 gap-16 text-center text-sm">
            <div><strong>TEACHER NAME</strong><div class="border-t border-slate-700 pt-1 font-medium">Class Adviser</div></div>
            <div><strong>BENJIE S. POLO</strong><div class="border-t border-slate-700 pt-1 font-medium">Dean / Program Head</div></div>
        </div>
    </div>
@endforeach
</div>
</body>
</html>