<section class="mb-4" aria-labelledby="dashboard-classes-heading">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <div class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M6 6h10"></path><path d="M6 10h10"></path></svg>
            <h2 id="dashboard-classes-heading" class="mb-0">
                {{ $user->role === 'admin' ? 'Daftar Kelas Aktif' : 'Kelas Pelajaran Saya' }} 
                <span class="badge badge-soft ms-1">{{ $classCount }}</span>
            </h2>
        </div>
    </div>
    <div class="row g-3">
        @forelse($classes as $classroom)
            <div class="col-md-6">
                @include('classes.card')
            </div>
        @empty
            <div class="col-12">
                <div class="empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted mb-2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path></svg>
                    <p class="mb-0 fw-semibold">Belum ada kelas yang terdaftar atau ditugaskan.</p>
                    @if($user->role === 'admin')
                        <div class="mt-3">
                            <a class="btn btn-primary btn-sm" href="/admin/setup">+ Atur Tahun Ajaran & Kelas</a>
                        </div>
                    @else
                        <div class="small-muted mt-2">Guru atau administrator sekolah akan mendaftarkan kelas Anda.</div>
                    @endif
                </div>
            </div>
        @endforelse
    </div>
</section>
