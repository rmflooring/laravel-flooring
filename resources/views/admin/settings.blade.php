<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-3xl font-bold mb-6">Admin Dashboard</h1>

                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <strong>Welcome, {{ auth()->user()->name }}!</strong><br>
                        Email: {{ auth()->user()->email }}<br>
                        You are logged in with full <strong>Admin</strong> privileges.
                    </div>

                    <h2 class="text-2xl font-semibold mb-4">Role & Permission Overview (Spatie)</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="text-lg font-medium mb-2">Your Assigned Roles</h3>
                            <ul class="border border-gray-200 rounded-lg divide-y divide-gray-200">
                                @forelse(auth()->user()->roles as $role)
                                    <li class="px-4 py-3">{{ $role->name }}</li>
                                @empty
                                    <li class="px-4 py-3 text-gray-500">No roles assigned</li>
                                @endforelse
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium mb-2">All Roles in System</h3>
                            <ul class="border border-gray-200 rounded-lg divide-y divide-gray-200">
                                @foreach(\Spatie\Permission\Models\Role::all() as $role)
                                    <li class="px-4 py-3">{{ $role->name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <h2 class="text-2xl font-semibold mb-4">Quick Admin Actions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="{{ route('admin.users.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            Manage Users
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
    			Manage Roles & Permissions
			</a>
						 <a href="{{ route('admin.users.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            Manage Users
                        </a>
                        <a href="{{ route('pages.settings.integrations.microsoft.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            Manage Calendar Settings
                        </a>
                        <a href="{{ route('admin.settings.branding') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            Branding
                        </a>
                        <a href="{{ route('admin.settings.mail') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            Mail Settings
                        </a>
                        <a href="{{ route('admin.settings.email-templates.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            System Email Templates
                        </a>
                        <a href="{{ route('admin.settings.calendar-templates.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            Calendar Entry Templates
                        </a>
                        <a href="{{ route('admin.settings.sms') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            SMS Notifications
                        </a>
                        <a href="{{ route('admin.settings.sms-templates.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            SMS Templates
                        </a>
                        <a href="{{ route('admin.document-templates.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            Document Templates
                        </a>
                        <a href="{{ route('admin.conditions.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            Conditions
                        </a>
                        <a href="{{ route('admin.settings.storage') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            Storage Settings
                        </a>
                        <a href="{{ route('admin.payment-terms.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            Payment Terms
                        </a>
                        <a href="{{ url('/') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-6 rounded-lg text-center block">
                            Back to Home
                        </a>
                    </div>

                    <p class="mt-8 text-sm text-gray-600">
                        This is your central admin panel. Add more sections here as you build your CRM.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
