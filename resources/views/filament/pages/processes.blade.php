<x-filament-panels::page>
    @php
        $processInfo = $this->getProcessInfo();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <!-- Process Statistics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                <x-heroicon-o-chart-bar class="inline-block w-5 h-5 mr-2" />
                Process Statistics
            </h3>
            
            @if(isset($processInfo['resource_usage']))
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Processes:</span>
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $processInfo['resource_usage']['total'] }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Running:</span>
                        <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $processInfo['resource_usage']['running'] }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Sleeping:</span>
                        <span class="text-sm font-bold text-yellow-600 dark:text-yellow-400">{{ $processInfo['resource_usage']['sleeping'] }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Zombie:</span>
                        <span class="text-sm font-bold {{ $processInfo['resource_usage']['zombie'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}">
                            {{ $processInfo['resource_usage']['zombie'] }}
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Critical Services -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 col-span-2">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                <x-heroicon-o-server class="inline-block w-5 h-5 mr-2" />
                Critical Services
            </h3>
            
            @if(isset($processInfo['critical_services']))
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($processInfo['critical_services'] as $service)
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $service['name'] }}</span>
                            <div class="flex items-center space-x-1">
                                @if($service['active'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        running
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        stopped
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Top Processes by CPU -->
    @if(isset($processInfo['system_processes']) && !empty($processInfo['system_processes']))
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                <x-heroicon-o-cpu-chip class="inline-block w-5 h-5 mr-2" />
                Top Processes by CPU Usage
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">PID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">CPU %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Memory %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Command</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($processInfo['system_processes'] as $process)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $process['user'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $process['pid'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ (float)$process['cpu'] > 50 ? 'bg-red-100 text-red-800' : ((float)$process['cpu'] > 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                    {{ $process['cpu'] }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ (float)$process['memory'] > 10 ? 'bg-red-100 text-red-800' : ((float)$process['memory'] > 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                    {{ $process['memory'] }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $process['stat'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate">
                                <span title="{{ $process['command'] }}">{{ $process['command'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Recent Process Activity -->
    @if(isset($processInfo['recent_activity']) && !empty($processInfo['recent_activity']))
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                <x-heroicon-o-clock class="inline-block w-5 h-5 mr-2" />
                Recent Process Activity
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">PID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Parent PID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Running Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Command</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($processInfo['recent_activity'] as $activity)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $activity['pid'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $activity['ppid'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $activity['etime'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate">
                                <span title="{{ $activity['command'] }}">{{ $activity['command'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</x-filament-panels::page>
