<p class="mt-2 text-sm text-slate-500">Enter this employee's additional details using the fields configured for your organization.</p>
@if($additionalFields->isEmpty())
    <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
        <p class="font-medium text-slate-700">No additional fields configured yet.</p>
        <a href="{{ route('hr.person.additional-information') }}" class="mt-3 inline-block text-sm font-semibold text-rose-600">Manage additional fields →</a>
    </div>
@else
    <div class="mt-7 grid gap-6 sm:grid-cols-2">
        @foreach($additionalFields as $field)
            <div>
                <label for="additional-field-{{ $field->id }}" class="mb-2 block text-sm font-medium text-slate-700">{{ $field->name }}</label>
                @if($field->type === 'select')
                    <select id="additional-field-{{ $field->id }}" name="additional_values[{{ $field->id }}]" data-additional-field="{{ $field->id }}" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm">
                        <option value="">Select an option</option>
                        @foreach($field->options ?? [] as $option)
                            <option value="{{ $option }}" @selected(($additionalValues[$field->id] ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="additional-field-{{ $field->id }}" name="additional_values[{{ $field->id }}]" data-additional-field="{{ $field->id }}" type="{{ $field->type === 'number' ? 'number' : ($field->type === 'date' ? 'date' : 'text') }}" @if($field->type === 'number') step="any" @elseif($field->type === 'text') maxlength="5000" @endif value="{{ $additionalValues[$field->id] ?? '' }}" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm">
                @endif
            </div>
        @endforeach
    </div>
@endif
