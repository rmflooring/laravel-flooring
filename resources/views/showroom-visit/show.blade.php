<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Your Showroom Visit — {{ $vars['company_name'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col items-center px-4 py-10">
<div class="w-full max-w-md">

    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ $vars['company_name'] }}</h1>
        @if ($vars['showroom_address'])
            <p class="text-gray-500 text-sm mt-1">{{ $vars['showroom_address'] }}</p>
        @endif
    </div>

    {{-- Result of an action just taken --}}
    @if (session('public_status') === 'cancelled')
        <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-900 text-sm">
            Your visit has been cancelled — we've let our team know. Whenever you're ready to rebook, just give us a call.
        </div>
    @elseif (session('public_status') === 'change_requested')
        <div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-900 text-sm">
            Thanks! We've received your request and will be in touch shortly to confirm a new time.
            Your current booking stays in place until we confirm the change.
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900">Your showroom visit</h2>
        <p class="text-sm text-gray-500 mt-1">Hi {{ $vars['customer_first_name'] }}, here are your appointment details.</p>

        <dl class="mt-5 space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Date</dt>
                <dd class="font-medium text-gray-900 text-right {{ $visit->status === 'cancelled' ? 'line-through' : '' }}">{{ $visit->scheduled_at->format('l, F j, Y') }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Time</dt>
                <dd class="font-medium text-gray-900 text-right {{ $visit->status === 'cancelled' ? 'line-through' : '' }}">{{ $vars['visit_time'] }} – {{ $vars['visit_end_time'] }}</dd>
            </div>
            @if ($vars['host_name'])
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Meeting with</dt>
                    <dd class="font-medium text-gray-900 text-right">{{ $vars['host_name'] }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Status</dt>
                <dd class="text-right">
                    @php
                        $publicLabels = [
                            'scheduled'        => ['Confirmed', 'bg-green-100 text-green-800'],
                            'change_requested' => ['Change requested', 'bg-amber-100 text-amber-800'],
                            'completed'        => ['Completed', 'bg-gray-100 text-gray-700'],
                            'no_show'          => ['Missed', 'bg-gray-100 text-gray-700'],
                            'cancelled'        => ['Cancelled', 'bg-red-100 text-red-800'],
                        ];
                        [$lbl, $cls] = $publicLabels[$visit->status] ?? [ucfirst($visit->status), 'bg-gray-100 text-gray-700'];
                    @endphp
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">{{ $lbl }}</span>
                </dd>
            </div>
        </dl>

        @if ($visit->status === 'change_requested' && ! session('public_status'))
            <p class="mt-5 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-3">
                We've received your request to change this time and will contact you to confirm.
            </p>
        @endif

        @if ($visit->customerCanModify())
            @if ($errors->any())
                <div class="mt-5 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <div class="mt-6 space-y-3">
                @if ($visit->status === 'scheduled')
                    <details class="group rounded-xl border border-gray-200" @if ($errors->has('change_request_preferred')) open @endif>
                        <summary class="cursor-pointer list-none px-4 py-3 font-medium text-blue-700 flex justify-between items-center">
                            Request a different time
                            <span class="text-gray-400 group-open:rotate-180 transition">▾</span>
                        </summary>
                        <form method="POST" action="{{ route('showroom-visit.public.request-change', $visit->token) }}" class="px-4 pb-4 space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Which days / times work better for you?</label>
                                <textarea name="change_request_preferred" rows="3" required maxlength="1000"
                                          placeholder="e.g. Any weekday after 3pm, or Saturday morning"
                                          class="w-full rounded-lg border border-gray-300 p-2.5 text-sm">{{ old('change_request_preferred') }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Anything else? <span class="text-gray-400">(optional)</span></label>
                                <textarea name="change_request_note" rows="2" maxlength="2000"
                                          class="w-full rounded-lg border border-gray-300 p-2.5 text-sm">{{ old('change_request_note') }}</textarea>
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                                Send request
                            </button>
                        </form>
                    </details>
                @endif

                <details class="group rounded-xl border border-gray-200">
                    <summary class="cursor-pointer list-none px-4 py-3 font-medium text-red-700 flex justify-between items-center">
                        Cancel my visit
                        <span class="text-gray-400 group-open:rotate-180 transition">▾</span>
                    </summary>
                    <form method="POST" action="{{ route('showroom-visit.public.cancel', $visit->token) }}" class="px-4 pb-4 space-y-3"
                          onsubmit="return confirm('Cancel your showroom visit?')">
                        @csrf
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Reason <span class="text-gray-400">(optional)</span></label>
                            <textarea name="cancellation_reason" rows="2" maxlength="2000"
                                      class="w-full rounded-lg border border-gray-300 p-2.5 text-sm"></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                            Cancel visit
                        </button>
                    </form>
                </details>
            </div>
        @endif
    </div>

    <div class="text-center text-sm text-gray-500 mt-6 space-y-1">
        <p>Questions? We're happy to help.</p>
        @if ($vars['showroom_phone'])
            <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $vars['showroom_phone']) }}" class="text-blue-600">{{ $vars['showroom_phone'] }}</a></p>
        @endif
        @if ($vars['showroom_email'])
            <p><a href="mailto:{{ $vars['showroom_email'] }}" class="text-blue-600">{{ $vars['showroom_email'] }}</a></p>
        @endif
    </div>
</div>
</body>
</html>
