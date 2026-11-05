<section class="panel report-filters" style="padding:18px;margin-bottom:22px">
    <form method="GET" action="{{ $action }}">
        <div class="form-grid">
            <div class="field">
                <label for="reportSearch">Cari laporan</label>
                <input id="reportSearch" name="q" value="{{ $searchTerm }}" placeholder="Kode, judul, deskripsi, atau pelapor">
            </div>
            <div class="field">
                <label for="reportStatus">Status</label>
                <select id="reportStatus" name="status">
                    <option value="Semua" {{ $activeStatus === 'Semua' ? 'selected' : '' }}>Semua status</option>
                    <option value="Diajukan" {{ $activeStatus === 'Diajukan' ? 'selected' : '' }}>Diajukan</option>
                    <option value="Diproses" {{ $activeStatus === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                    <option value="Selesai" {{ $activeStatus === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="Ditolak" {{ $activeStatus === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="field">
                <label for="reportLocation">Ruangan</label>
                <select id="reportLocation" name="location">
                    <option value="">Semua ruangan</option>
                    @foreach ($locations as $locationOption)
                        <option value="{{ $locationOption }}" {{ $location === $locationOption ? 'selected' : '' }}>{{ $locationOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="reportCategory">Kategori kerusakan</label>
                <select id="reportCategory" name="category">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption }}" {{ $category === $categoryOption ? 'selected' : '' }}>{{ $categoryOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="reportPriority">Tingkat kerusakan</label>
                <select id="reportPriority" name="priority">
                    <option value="">Semua tingkat</option>
                    <option value="Belum dinilai" {{ $priority === 'Belum dinilai' ? 'selected' : '' }}>Belum dinilai</option>
                    <option value="Normal" {{ $priority === 'Normal' ? 'selected' : '' }}>Normal</option>
                    <option value="Urgent" {{ $priority === 'Urgent' ? 'selected' : '' }}>Urgent</option>
                    <option value="Darurat" {{ $priority === 'Darurat' ? 'selected' : '' }}>Darurat</option>
                </select>
            </div>
            <div class="field">
                <label for="reportDate">Tanggal laporan</label>
                <input id="reportDate" name="date" type="text" inputmode="numeric" maxlength="10" value="{{ $date }}" placeholder="Contoh: 8 atau 08-09-2026">
            </div>
            <div class="field">
                <label for="reportMonth">Bulan laporan</label>
                <select id="reportMonth" name="month">
                    <option value="">Semua bulan</option>
                    @foreach (range(1, 12) as $monthNumber)
                        <option value="{{ $monthNumber }}" {{ (string) $month === (string) $monthNumber ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($monthNumber)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="reportYear">Tahun laporan</label>
                <select id="reportYear" name="year">
                    <option value="">Semua tahun</option>
                    @foreach (range(now()->year, now()->year - 5) as $yearOption)
                        <option value="{{ $yearOption }}" {{ (string) $year === (string) $yearOption ? 'selected' : '' }}>{{ $yearOption }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-footer">
            <a class="btn btn-soft" href="{{ $action }}">Reset filter</a>
            <button class="btn btn-primary" type="submit">Cari laporan</button>
        </div>
    </form>
</section>
