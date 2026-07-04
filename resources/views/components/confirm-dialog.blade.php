<div
    x-data="{
        open: false, form: null, title: 'Konfirmasi tindakan', message: '',
        show(event) {
            this.form = event.form;
            this.title = event.title || 'Konfirmasi tindakan';
            this.message = event.message || 'Apakah Anda yakin ingin melanjutkan?';
            this.open = true;
        },
        proceed() {
            if (!this.form) return;
            this.form.dataset.confirmed = 'true';
            this.form.requestSubmit();
            this.open = false;
        }
    }"
    @confirm-action.window="show($event.detail)"
    @keydown.escape.window="open = false">
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[110] bg-slate-950/60 backdrop-blur-sm" @click="open = false"></div>
    <div x-cloak x-show="open" x-transition class="fixed inset-0 z-[120] grid place-items-center p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900" @click.stop>
            <div class="grid size-12 place-items-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-950/60">
                <i data-lucide="triangle-alert" class="size-6"></i>
            </div>
            <h2 class="mt-5 text-lg font-bold" x-text="title"></h2>
            <p class="mt-2 text-sm leading-6 text-slate-500" x-text="message"></p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Batal</button>
                <button type="button" @click="proceed()" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Ya, lanjutkan</button>
            </div>
        </div>
    </div>
</div>
