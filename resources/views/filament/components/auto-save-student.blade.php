<div x-data="{
    storageKey: 'student_form_draft_' + @js($recordId ?? 'new'),
    hasDraft: false,
    init() {
        // Kiểm tra bản nháp ngay khi load
        const savedData = localStorage.getItem(this.storageKey);
        if (savedData) {
            this.hasDraft = true;
        }

        // Tự động lưu mỗi khi form thay đổi (debounce 1s để tối ưu)
        this.$watch('$wire.data', value => {
            if (Object.values(value).some(v => v !== null && v !== '')) {
                localStorage.setItem(this.storageKey, JSON.stringify(value));
            }
        }, { deep: true });
    },
    restore() {
        const savedData = localStorage.getItem(this.storageKey);
        if (savedData) {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                // Chỉ điền vào những ô đang trống hoặc khác dữ liệu hiện tại
                if (data[key] !== null && data[key] !== undefined) {
                    this.$wire.set('data.' + key, data[key], false);
                }
            });
            
            new FilamentNotification()
                .title('Đã khôi phục dữ liệu nháp')
                .success()
                .send();
                
            this.hasDraft = false;
            localStorage.removeItem(this.storageKey);
        }
    },
    discard() {
        localStorage.removeItem(this.storageKey);
        this.hasDraft = false;
    }
}" 
x-on:form-submitted.window="localStorage.removeItem(storageKey); hasDraft = false;"
class="w-full mb-4"
x-show="hasDraft"
x-transition
style="display: none;">
    <div class="p-4 rounded-xl border border-primary-500 bg-primary-50 dark:bg-primary-950/20 flex items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-primary-500 rounded-lg text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-primary-900 dark:text-primary-100">Phát hiện dữ liệu nháp</h4>
                <p class="text-sm text-primary-700 dark:text-primary-300">Bạn có một bản nháp chưa lưu từ lần nhập trước. Bạn có muốn khôi phục không?</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="restore()" class="px-4 py-2 bg-primary-600 hover:bg-primary-500 text-white text-sm font-bold rounded-lg transition-colors shadow-sm">
                Khôi phục ngay
            </button>
            <button type="button" @click="discard()" class="px-4 py-2 text-primary-700 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200 text-sm font-medium transition-colors">
                Bỏ qua
            </button>
        </div>
    </div>
</div>
