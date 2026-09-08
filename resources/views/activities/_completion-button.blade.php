{{-- Shared Completion Button Partial --}}
{{-- Usage: @include('activities._completion-button', ['label' => 'Đã học xong...']) --}}
<div class="pt-4 text-center">
    <button @click="markCompleted()" :disabled="saving"
        :class="isCompleted ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : (isTrialMode ? 'bg-teal-500/20 text-teal-300 border border-teal-500/40 hover:bg-teal-500/30' : 'btn-primary')"
        class="!w-auto !py-2.5 px-8 text-sm shadow-glow-blue transition-all">
        <span x-show="isTrialMode">✨ Đang học thử (Không lưu tiến trình)</span>
        <span x-show="!isTrialMode && !saving && !isCompleted">{{ $label ?? 'Hoàn thành ✓' }}</span>
        <span x-show="!isTrialMode && !saving && isCompleted">✅ Đã hoàn thành hoạt động này</span>
        <span x-show="!isTrialMode && saving">⏳ Đang lưu tiến trình...</span>
    </button>
</div>
