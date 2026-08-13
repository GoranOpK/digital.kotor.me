<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    @if (Route::has('profile.destroy'))
        <button
            type="button"
            data-kk-profile-delete-open
            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
        >{{ __('Delete Account') }}</button>

        <dialog
            data-kk-profile-delete-dialog
            class="rounded-lg p-0 shadow-xl border border-gray-200 w-full max-w-lg backdrop:bg-gray-900/60"
        >
            <form method="post" action="{{ route('profile.destroy') }}" class="p-6" data-kk-profile-delete-form>
                @csrf
                @method('delete')

                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>

                <div class="mt-6">
                    <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="{{ __('Password') }}"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        data-kk-profile-delete-cancel
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                    >{{ __('Cancel') }}</button>

                    <x-danger-button>
                        {{ __('Delete Account') }}
                    </x-danger-button>
                </div>
            </form>
        </dialog>

        <script>
        (function () {
            var openBtn = document.querySelector('[data-kk-profile-delete-open]');
            var dialog = document.querySelector('[data-kk-profile-delete-dialog]');
            var cancelBtn = document.querySelector('[data-kk-profile-delete-cancel]');
            if (!openBtn || !dialog) {
                return;
            }

            openBtn.addEventListener('click', function (event) {
                event.preventDefault();
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            });

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    if (typeof dialog.close === 'function') {
                        dialog.close();
                    }
                });
            }
        })();
        </script>
    @endif
</section>
