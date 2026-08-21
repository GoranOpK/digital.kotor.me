<div class="flex flex-wrap gap-3 mb-6 text-sm">
    <a href="{{ route('admin.e-payments.payment-types.index') }}" class="{{ request()->routeIs('admin.e-payments.payment-types.*') ? 'font-semibold text-indigo-700' : 'text-indigo-700 hover:underline' }}">Katalog e-Plaćanja</a>
    <a href="{{ route('admin.e-payments.transactions.index') }}" class="{{ request()->routeIs('admin.e-payments.transactions.*') ? 'font-semibold text-indigo-700' : 'text-indigo-700 hover:underline' }}">Transakcije</a>
    <a href="{{ route('admin.e-payments.settings.edit') }}" class="{{ request()->routeIs('admin.e-payments.settings.*') ? 'font-semibold text-indigo-700' : 'text-indigo-700 hover:underline' }}">Nova plaćanja</a>
</div>
