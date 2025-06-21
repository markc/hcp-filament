<x-filament-panels::page>
    @php
        $mailInfo = $this->getMailInfo();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Mail System Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                <x-heroicon-o-envelope class="inline-block w-5 h-5 mr-2" />
                Mail System Status
            </h3>
            
            @if(isset($mailInfo['mail_system']))
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $mailInfo['mail_system']['status']['running'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $mailInfo['mail_system']['status']['status'] }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Version:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $mailInfo['mail_system']['version'] }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Queue Size:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $mailInfo['mail_system']['queue_size'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                            {{ $mailInfo['mail_system']['queue_size'] }} messages
                        </span>
                    </div>
                </div>
            @else
                <div class="text-sm text-red-600 dark:text-red-400">Unable to retrieve mail system status</div>
            @endif
        </div>

        <!-- Statistics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                <x-heroicon-o-chart-bar class="inline-block w-5 h-5 mr-2" />
                Statistics
            </h3>
            
            @if(isset($mailInfo['statistics']))
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Domains:</span>
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $mailInfo['statistics']['total_domains'] }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Active Domains:</span>
                        <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $mailInfo['statistics']['active_domains'] }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Mailboxes:</span>
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $mailInfo['statistics']['total_mailboxes'] }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Active Mailboxes:</span>
                        <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $mailInfo['statistics']['active_mailboxes'] }}</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                <x-heroicon-o-clock class="inline-block w-5 h-5 mr-2" />
                Today's Activity
            </h3>
            
            @if(isset($mailInfo['recent_activity']))
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">New Domains:</span>
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ $mailInfo['recent_activity']['new_domains_today'] }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">New Mailboxes:</span>
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ $mailInfo['recent_activity']['new_mailboxes_today'] }}</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Disk Usage -->
        @if(isset($mailInfo['disk_usage']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                <x-heroicon-o-circle-stack class="inline-block w-5 h-5 mr-2" />
                Mail Storage
            </h3>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Used Space:</span>
                    <span class="text-sm font-bold text-orange-600 dark:text-orange-400">{{ $mailInfo['disk_usage']['formatted'] }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Recent Mail Logs -->
    @if(isset($mailInfo['logs']) && !empty($mailInfo['logs']))
    <div class="mt-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    <x-heroicon-o-document-text class="inline-block w-5 h-5 mr-2" />
                    Recent Mail Logs
                </h3>
            </div>
            
            <div class="p-6">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 max-h-96 overflow-y-auto">
                    <pre class="text-xs text-gray-800 dark:text-gray-200 font-mono whitespace-pre-wrap">@foreach($mailInfo['logs'] as $log){{ $log }}
@endforeach</pre>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-filament-panels::page>
