@extends('layouts.app')

@section('title', 'Submit Report')

@section('content')
    @php
        $categories = collect($categories ?? []);
        $customCategoryValue = $customCategoryValue ?? '__custom_category__';
        $selectedType = old('type', request('type', 'lost'));
        $oldCategory = old('category');
        $usesCustomCategory = $oldCategory === $customCategoryValue || ($oldCategory && ! $categories->contains($oldCategory));
        $customCategoryName = old('category_custom', $usesCustomCategory && $oldCategory !== $customCategoryValue ? $oldCategory : '');
    @endphp

    <section class="page-head">
        <div>
            <p class="eyebrow">User Side</p>
            <h1>Submit Item Report</h1>
            <p class="muted">Lost and found submissions enter the pending queue for review.</p>
        </div>
        <a class="ghost-button" href="{{ route('home') }}">View Board</a>
    </section>

    <section class="panel">
        <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="form-grid">
            @csrf

            <div class="field span-2">
                <span class="radio-label">Report Type</span>
                <div class="segmented">
                    <label class="segment">
                        <input type="radio" name="type" value="lost" @checked($selectedType === 'lost')>
                        <span>Lost Item</span>
                    </label>
                    <label class="segment">
                        <input type="radio" name="type" value="found" @checked($selectedType === 'found')>
                        <span>Found Item</span>
                    </label>
                </div>
            </div>

            <div class="field">
                <label for="item_name">Item Name</label>
                <input class="input" id="item_name" name="item_name" value="{{ old('item_name') }}" required>
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

            <div class="field">
                <label for="happened_at">Date & Time</label>
                <input class="input" id="happened_at" type="datetime-local" name="happened_at" value="{{ old('happened_at') }}">
            </div>

            <div class="field">
                <label id="location-label" for="location">{{ $selectedType === 'found' ? 'Pick Up Location' : 'Last Known Location' }}</label>
                <input class="input" id="location" name="location" value="{{ old('location') }}" required>
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
                <label for="contact_name">Contact Name</label>
                <input class="input" id="contact_name" name="contact_name" value="{{ old('contact_name', auth()->user()->name) }}" required>
            </div>

            <div class="field">
                <label for="contact_phone">Cellphone Number</label>
                <input class="input" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', auth()->user()->contact_phone) }}" inputmode="tel" autocomplete="tel" required>
            </div>

            <div class="field">
                <label for="contact_email">Contact Email (optional)</label>
                <input class="input" id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', auth()->user()->email) }}" autocomplete="email">
            </div>

            <div class="span-2" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button class="button" type="submit">Submit Report</button>
                <a class="ghost-button" href="{{ route('home') }}">Cancel</a>
            </div>
        </form>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const reportTypeInputs = document.querySelectorAll('input[name="type"]');
            const locationLabel = document.getElementById('location-label');
            const categorySelect = document.getElementById('category');
            const customCategoryInput = document.getElementById('category_custom');

            if (!locationLabel || !categorySelect || !customCategoryInput) {
                return;
            }

            const customCategoryValue = categorySelect.dataset.customCategoryValue || '';

            reportTypeInputs.forEach((reportTypeInput) => {
                reportTypeInput.addEventListener('change', () => {
                    locationLabel.textContent = reportTypeInput.value === 'found'
                        ? 'Pick Up Location'
                        : 'Last Known Location';
                });
            });

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
