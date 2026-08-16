@extends('layouts.app')

@section('title', 'Submit Report')

@section('content')
    @php
        $categories = collect($categories ?? []);
        $customCategoryValue = $customCategoryValue ?? '__custom_category__';
        $selectedType = old('type', request('type', 'lost'));
        $selectedType = in_array($selectedType, ['lost', 'found'], true) ? $selectedType : 'lost';
        $oldCategory = old('category');
        $usesCustomCategory = $oldCategory === $customCategoryValue || ($oldCategory && ! $categories->contains($oldCategory));
        $customCategoryName = old('category_custom', $usesCustomCategory && $oldCategory !== $customCategoryValue ? $oldCategory : '');
        $schoolId = auth()->user()?->name ?? '';
    @endphp

    <section class="page-head">
        <div>
            <p class="eyebrow">User Side</p>
            <h1>Submit Item Report</h1>
            <p class="muted">Lost and found submissions enter the pending queue for review.</p>
        </div>
        <a class="ghost-button" href="{{ route('home') }}">View Board</a>
    </section>

    <section class="panel report-form-panel">
        <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="form-grid">
            @csrf

            <input type="hidden" name="type" value="{{ $selectedType }}">

            <div class="field">
                <label for="item_name">Item Name</label>
                <input class="input" id="item_name" name="item_name" value="{{ old('item_name') }}">
            </div>

            <div class="field">
                <label for="category">Category</label>
                <select class="select" id="category" name="category" data-custom-category-value="{{ $customCategoryValue }}" required>
                    <option value="" @selected(! $oldCategory)>Choose category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected($oldCategory === $category)>{{ $category }}</option>
                    @endforeach
                    <option value="{{ $customCategoryValue }}" @selected($usesCustomCategory)>Add new category...</option>
                </select>
                <input
                    class="input"
                    id="category_custom"
                    name="category_custom"
                    value="{{ $customCategoryName }}"
                    placeholder="New category name"
                    maxlength="80"
                    @if (! $usesCustomCategory) hidden disabled @endif
                    @required($usesCustomCategory)
                >
            </div>

            <div class="field span-2" style="max-width: 420px; margin: 0 auto; width: 100%;">
                <label for="happened_at" style="text-align: center;">Date & Time</label>
                <input class="input" id="happened_at" type="datetime-local" name="happened_at" value="{{ old('happened_at') }}" style="text-align: center;" data-report-date-time>
                <p id="happened_at_error" class="muted" style="display: {{ $errors->has('happened_at') ? 'block' : 'none' }}; color: #dc2626; margin-top: 6px; text-align: center;">{{ $errors->first('happened_at') }}</p>
            </div>


            <div class="field span-2">
                <label for="description">Description</label>
                <textarea class="textarea" id="description" name="description">{{ old('description') }}</textarea>
            </div>

            <div class="field">
                <label for="photo">Photo</label>
                <input class="input" id="photo" type="file" name="photo" accept="image/*">
            </div>

            <div class="field">
                <label for="contact_name">School ID</label>
                <input class="input" id="contact_name" name="contact_name" value="{{ old('contact_name', $schoolId) }}" required data-school-id-input>
            </div>

            <div class="field">
                <label for="contact_phone">Cellphone Number</label>
                <input class="input" id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" inputmode="tel" autocomplete="tel" required>
            </div>

            <div class="field">
                <label for="contact_email">Contact Email (optional)</label>
                <input class="input" id="contact_email" type="email" name="contact_email" value="{{ old('contact_email') }}" autocomplete="email">
            </div>

            <div class="span-2" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button class="button" type="submit">Submit Report</button>
                <a class="ghost-button" href="{{ route('home') }}">Cancel</a>
            </div>
        </form>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const categorySelect = document.getElementById('category');
            const customCategoryInput = document.getElementById('category_custom');
            const reportDateTimeInput = document.querySelector('[data-report-date-time]');
            const reportDateTimeError = document.getElementById('happened_at_error');
            const reportForm = reportDateTimeInput?.closest('form');

            if (reportDateTimeInput && reportDateTimeError && reportForm) {
                const formatDateTimeLocal = (date) => {
                    const pad = (value) => String(value).padStart(2, '0');

                    return [
                        date.getFullYear(),
                        pad(date.getMonth() + 1),
                        pad(date.getDate()),
                    ].join('-') + 'T' + [
                        pad(date.getHours()),
                        pad(date.getMinutes()),
                    ].join(':');
                };

                const validateReportDateTime = () => {
                    const now = new Date();
                    const maxDateTime = formatDateTimeLocal(now);
                    const hasFutureDateTime = reportDateTimeInput.value && new Date(reportDateTimeInput.value) > now;

                    reportDateTimeInput.max = maxDateTime;
                    reportDateTimeInput.setCustomValidity(hasFutureDateTime ? 'Invalid date/time' : '');
                    reportDateTimeError.textContent = hasFutureDateTime ? 'Invalid date/time' : '';
                    reportDateTimeError.style.display = hasFutureDateTime ? 'block' : 'none';

                    return !hasFutureDateTime;
                };

                reportDateTimeInput.addEventListener('input', validateReportDateTime);
                reportDateTimeInput.addEventListener('change', validateReportDateTime);
                reportForm.addEventListener('submit', (event) => {
                    if (!validateReportDateTime()) {
                        event.preventDefault();
                        reportDateTimeInput.reportValidity();
                    }
                });

                validateReportDateTime();
            }

            if (!categorySelect || !customCategoryInput) {
                return;
            }

            const customCategoryValue = categorySelect.dataset.customCategoryValue || '';

            categorySelect.addEventListener('change', updateCustomCategoryField);

            function updateCustomCategoryField() {
                const usesCustomCategory = categorySelect.value === customCategoryValue;

                customCategoryInput.hidden = !usesCustomCategory;
                customCategoryInput.disabled = !usesCustomCategory;
                customCategoryInput.required = usesCustomCategory;

                if (usesCustomCategory) {
                    customCategoryInput.focus();
                } else {
                    customCategoryInput.value = '';
                }
            }
        });
    </script>
@endsection
