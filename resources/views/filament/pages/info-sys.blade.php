<x-filament-panels::page>
    @php
        $info = $this->getSystemInfo();
        $systemService = app(\App\Services\SystemService::class);
    @endphp

    <div class="space-y-6">
        {{-- System Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- CPU Usage --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">CPU Usage</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $info['cpu']['usage'] ?? 0 }}%
                            </p>
                        </div>
                        <div class="rounded-full p-3 {{ ($info['cpu']['usage'] ?? 0) > 80 ? 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400' : (($info['cpu']['usage'] ?? 0) > 60 ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400' : 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400') }}">
                            <x-heroicon-o-cpu-chip class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, $info['cpu']['usage'] ?? 0) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Memory Usage --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Memory Usage</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $info['memory']['usage_percent'] ?? 0 }}%
                            </p>
                        </div>
                        <div class="rounded-full p-3 {{ ($info['memory']['usage_percent'] ?? 0) > 80 ? 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400' : (($info['memory']['usage_percent'] ?? 0) > 60 ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400' : 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400') }}">
                            <x-heroicon-o-circuit-board class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, $info['memory']['usage_percent'] ?? 0) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        {{ $systemService->formatBytes($info['memory']['used'] ?? 0) }} / 
                        {{ $systemService->formatBytes($info['memory']['total'] ?? 0) }}
                    </p>
                </div>
            </div>

            {{-- Disk Usage --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Disk Usage</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $info['disk']['usage_percent'] ?? 0 }}%
                            </p>
                        </div>
                        <div class="rounded-full p-3 {{ ($info['disk']['usage_percent'] ?? 0) > 80 ? 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400' : (($info['disk']['usage_percent'] ?? 0) > 60 ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400' : 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400') }}">
                            <x-heroicon-o-circle-stack class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, $info['disk']['usage_percent'] ?? 0) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        {{ $systemService->formatBytes($info['disk']['used'] ?? 0) }} / 
                        {{ $systemService->formatBytes($info['disk']['total'] ?? 0) }}
                    </p>
                </div>
            </div>

            {{-- Load Average --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Load Average (1m)</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($info['load_average'][0] ?? 0, 2) }}
                            </p>
                        </div>
                        <div class="rounded-full p-3 bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400">
                            <x-heroicon-o-chart-bar class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>5m: {{ number_format($info['load_average'][1] ?? 0, 2) }}</span> • 
                        <span>15m: {{ number_format($info['load_average'][2] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- System Details --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- System Information --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        System Information
                    </h3>
                </div>
                <div class="fi-section-content p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Hostname</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $info['hostname'] ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">IP Address</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $info['host_ip'] ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Operating System</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $info['os'] ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Kernel Version</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $info['kernel'] ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Uptime</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $info['uptime'] ?? 'Unknown' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- CPU Information --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        CPU Information
                    </h3>
                </div>
                <div class="fi-section-content p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Processor</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $info['cpu']['name'] ?? 'Unknown CPU' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Cores</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $info['cpu']['cores'] ?? 0 }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-300">Current Usage</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $info['cpu']['usage'] ?? 0 }}%</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
