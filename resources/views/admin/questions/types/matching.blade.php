{{-- Matching Pairs Question Module --}}
<div x-show="singleQType === 'matching'" class="space-y-3" x-cloak>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-indigo-300 mb-1">
                Cột A (Từ vựng tiếng Anh - Mỗi dòng 1 từ): <span class="text-red-400">*</span>
            </label>
            <textarea name="matching_left" rows="3" placeholder="Solar PV&#10;Wind Turbine&#10;Geothermal" class="login-input text-xs font-mono"></textarea>
        </div>
        <div>
            <label class="block text-xs font-bold text-teal-300 mb-1">
                Cột B (Nghĩa tương ứng đúng vị trí dòng): <span class="text-red-400">*</span>
            </label>
            <textarea name="matching_right" rows="3" placeholder="Pin quang điện&#10;Tua bin gió&#10;Địa nhiệt" class="login-input text-xs font-mono"></textarea>
        </div>
    </div>
    <p class="text-[10px] text-gray-500">Mỗi dòng ở cột A sẽ tự động ghép cặp với dòng cùng thứ tự ở cột B.</p>
</div>
