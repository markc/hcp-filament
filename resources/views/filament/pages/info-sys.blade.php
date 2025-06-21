<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">System Information</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">PHP Version</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ PHP_VERSION }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Laravel Version</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ app()->version() }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Server OS</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ php_uname('s') }} {{ php_uname('r') }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Memory Limit</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ ini_get('memory_limit') }}</dd>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
