@extends('layouts.app')

@php($activePage = 'new-report')
@php($crumb = __('nav.menu_new_report'))

@section('title', __('nav.menu_new_report'))

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ __('form.eyebrow') }}</p>
        <h1>{{ __('form.title') }}</h1>
        <p>{{ __('form.desc') }}</p>
    </div>
</div>

<div class="form-layout">
    <form class="panel form-panel" method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
        @csrf
        <h2>{{ __('form.panel_title') }}</h2>
        <p>{{ __('form.panel_desc') }}</p>

        @if ($errors->any())
            <div class="error" style="margin-bottom:14px">
                <ul style="margin:0;padding-left:18px">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-grid">
            <div class="field">
                <label for="location">{{ __('form.label_location') }} <span class="required">*</span></label>
                <select id="location" name="location" required>
                    <option value="">{{ __('form.placeholder_location') }}</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc }}" {{ old('location') === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="category">{{ __('form.label_category') }} <span class="required">*</span></label>
                <select id="category" name="category" required>
                    <option value="">{{ __('form.placeholder_category') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="incident_date">{{ __('form.label_date') }} <span class="required">*</span></label>
                <input id="incident_date" name="incident_date" type="date" value="{{ old('incident_date', now()->toDateString()) }}" required>
            </div>

            <div class="field">
                <label for="priority">{{ __('form.label_priority') }}</label>
                <select id="priority" name="priority">
                    <option value="Normal" {{ old('priority', 'Normal') === 'Normal' ? 'selected' : '' }}>{{ __('common.priority_normal') }}</option>
                    <option value="Urgent" {{ old('priority') === 'Urgent' ? 'selected' : '' }}>{{ __('common.priority_urgent') }}</option>
                    <option value="Darurat" {{ old('priority') === 'Darurat' ? 'selected' : '' }}>{{ __('common.priority_darurat') }}</option>
                </select>
            </div>

            <div class="field full">
                <label for="title">{{ __('form.label_title') }} <span class="required">*</span></label>
                <input id="title" name="title" type="text" placeholder="{{ __('form.placeholder_title') }}" value="{{ old('title') }}" required>
            </div>

            <div class="field full">
                <label for="description">{{ __('form.label_description') }} <span class="required">*</span></label>
                <textarea id="description" name="description" placeholder="{{ __('form.placeholder_description') }}" required>{{ old('description') }}</textarea>
            </div>

            <div class="field full">
                <label for="evidence">{{ __('form.label_evidence') }} <span class="required">*</span></label>
                <label class="upload" for="evidence">
                    <strong>{{ __('form.evidence_button') }}</strong>
                    <span>{{ __('form.evidence_hint') }}</span>
                    <input id="evidence" name="evidence" type="file" accept="image/png,image/jpeg" hidden required>
                </label>
                <small class="muted" id="fileName"></small>
            </div>
        </div>

        <div class="form-footer">
            <a class="btn btn-soft" href="{{ route('dashboard') }}">{{ __('common.btn_cancel') }}</a>
            <button class="btn btn-primary" type="submit">{{ __('form.submit') }}</button>
        </div>
    </form>

    <aside class="process-panel">
        <h3>{{ __('form.process_title') }}</h3>
        <p>{{ __('form.process_desc') }}</p>

        <div class="process-step">
            <div class="process-num">1</div>
            <div><strong>{{ __('form.process_step_1_title') }}</strong><p>{{ __('form.process_step_1_desc') }}</p></div>
        </div>
        <div class="process-step">
            <div class="process-num">2</div>
            <div><strong>{{ __('form.process_step_2_title') }}</strong><p>{{ __('form.process_step_2_desc') }}</p></div>
        </div>
        <div class="process-step">
            <div class="process-num">3</div>
            <div><strong>{{ __('form.process_step_3_title') }}</strong><p>{{ __('form.process_step_3_desc') }}</p></div>
        </div>
        <div class="process-step">
            <div class="process-num">4</div>
            <div><strong>{{ __('form.process_step_4_title') }}</strong><p>{{ __('form.process_step_4_desc') }}</p></div>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
    // Menampilkan nama file yang dipilih di bawah tombol upload (lihat public/js/app.js -> initEvidencePreview)
    document.addEventListener('DOMContentLoaded', () => window.SchoolCare?.initEvidencePreview());
</script>
@endpush
