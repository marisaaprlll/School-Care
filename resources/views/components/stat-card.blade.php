{{--
    Kartu statistik di halaman Ringkasan.
    Pemakaian: <x-stat-card :label="$stat['label']" :value="$stat['value']" :note="$stat['note']" />
--}}
<div class="stat">
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value">{{ $value }}</div>
    <div class="stat-note">{{ $note }}</div>
</div>
