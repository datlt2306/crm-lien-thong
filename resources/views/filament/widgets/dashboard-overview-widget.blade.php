<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Commission Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900">Thống kê Commission</h3>
                <div class="h-80">
                    @livewire($commissionChart)
                </div>
            </div>

            <!-- Payment Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900">Thống kê Payment</h3>
                <div class="h-80">
                    @livewire($paymentChart)
                </div>
            </div>

            <!-- Student Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900">Thống kê Student</h3>
                <div class="h-80">
                    @livewire($studentChart)
                </div>
            </div>

            <!-- Wallet Transaction Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900">Thống kê Wallet Transaction</h3>
                <div class="h-80">
                    @livewire($walletTransactionChart)
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>