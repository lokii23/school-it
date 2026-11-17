{{-- The people suggested while typing a tag. Expects $field: body, draft or newComment --}}
<ul x-show="mn.open && mn.field === '{{ $field }}'" style="display:none"
    class="fd-pop absolute inset-x-0 top-full z-30 mt-1 max-h-60 overflow-y-auto rounded-xl py-1">
    <template x-for="(p, i) in mn.items" :key="p.id">
        <li>
            <button type="button" @mousedown.prevent="mnPick(p)" @mouseenter="mn.active = i"
                    :class="mn.active === i ? 'fd-sel' : ''"
                    class="flex w-full items-center gap-2.5 px-3 py-2 text-left">
                <template x-if="p.photo"><img :src="p.photo" alt="" class="h-7 w-7 shrink-0 rounded-full object-cover"></template>
                <template x-if="!p.photo"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#f6c945] text-[10px] font-bold text-[#0a1f55]" x-text="p.initials"></span></template>
                <span class="min-w-0">
                    <span class="fd-text block truncate text-sm font-semibold" x-text="p.name"></span>
                    <span class="fd-muted block truncate text-[11px] capitalize" x-text="p.sub"></span>
                </span>
            </button>
        </li>
    </template>
</ul>