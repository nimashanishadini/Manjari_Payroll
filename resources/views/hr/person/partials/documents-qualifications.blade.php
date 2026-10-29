<p class="mt-2 text-sm text-slate-500">Add education details and supporting employee documents. All categories are optional.</p>
<div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-600">
    PDF, JPG, PNG or WebP · Maximum 5MB per file · Up to 10 files per category. Select files, then use Save Documents & Qualifications below.
</div>

@foreach(['Education & Qualifications' => ['ol', 'al', 'higher_education'], 'Personal & Employment Documents' => ['nic', 'birth_certificate', 'driving_license', 'police_clearance', 'experience_letters', 'service_certificates']] as $group => $keys)
    <div class="mt-8">
        <h3 class="border-b border-slate-100 pb-3 text-sm font-bold text-slate-800">{{ $group }}</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @foreach($keys as $key)
                <article class="min-w-0 rounded-xl border border-slate-200 p-4 sm:p-5" data-document-category="{{ $key }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h4 class="text-sm font-semibold text-slate-900">{{ config('employee_documents.'.$key) }}</h4>
                        <span data-document-count class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500">No files saved</span>
                    </div>
                    @if(in_array($key, ['ol', 'al', 'higher_education']))
                        <div class="mt-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_100px]">
                            <div>
                                <label for="{{ $key }}-institution" class="text-xs font-medium text-slate-600">School / Institution</label>
                                <input id="{{ $key }}-institution" name="qualifications[{{ $key }}][institution]" data-qualification="institution" maxlength="255" placeholder="Institution name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
                            </div>
                            <div>
                                <label for="{{ $key }}-year" class="text-xs font-medium text-slate-600">Year</label>
                                <input id="{{ $key }}-year" name="qualifications[{{ $key }}][year]" data-qualification="year" type="number" min="1900" max="{{ now()->year }}" placeholder="YYYY" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
                            </div>
                        </div>
                        <label for="{{ $key }}-details" class="mt-3 block text-xs font-medium text-slate-600">Qualification / Results</label>
                        <textarea id="{{ $key }}-details" name="qualifications[{{ $key }}][details]" data-qualification="details" rows="2" maxlength="5000" placeholder="Qualifications, subjects, grades or other details" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"></textarea>
                    @endif
                    <div data-saved-documents class="mt-4 space-y-2"></div>
                    <label for="{{ $key }}-files" class="mt-4 block text-xs font-medium text-slate-600">{{ in_array($key, ['ol', 'al', 'higher_education']) ? 'Certificates / Results sheets' : 'Supporting files' }}</label>
                    <input id="{{ $key }}-files" type="file" name="uploads[{{ $key }}][]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" aria-describedby="{{ $key }}-selection" class="mt-2 block w-full min-w-0 text-xs text-slate-500 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-2 file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
                    <p id="{{ $key }}-selection" data-file-selection role="status" class="mt-2 break-words text-xs text-slate-500"></p>
                </article>
            @endforeach
        </div>
    </div>
@endforeach
