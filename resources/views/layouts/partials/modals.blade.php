{{-- Universal Pop-Up Modals for Edit & Delete --}}
<div class="aksara-popup-backdrop" id="aksaraDeleteModal" aria-hidden="true">
    <div class="aksara-popup-dialog aksara-popup-sm" role="dialog" aria-modal="true" aria-labelledby="aksaraDeleteTitle">
        <div class="aksara-popup-header">
            <div class="d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:34px;height:34px;background:var(--danger-bg);color:var(--danger);flex-shrink:0;" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                </span>
                <h3 class="h6 fw-bold mb-0 text-danger" id="aksaraDeleteTitle">Konfirmasi Penghapusan</h3>
            </div>
            <button type="button" class="aksara-popup-close" data-popup-close aria-label="Tutup">&times;</button>
        </div>
        <div class="aksara-popup-body">
            <p class="mb-0 text-secondary" id="aksaraDeleteMessage">Apakah Anda yakin ingin menghapus data ini?</p>
        </div>
        <div class="aksara-popup-footer">
            <button type="button" class="btn btn-outline-secondary" data-popup-close>Batal</button>
            <button type="button" class="btn btn-danger" id="aksaraDeleteConfirmBtn">Ya, Hapus Sekarang</button>
        </div>
    </div>
</div>

<div class="aksara-popup-backdrop" id="aksaraEditModal" aria-hidden="true">
    <div class="aksara-popup-dialog" role="dialog" aria-modal="true" aria-labelledby="aksaraEditTitle">
        <div class="aksara-popup-header">
            <h3 class="h5 fw-bold mb-0" id="aksaraEditTitle">Ubah Data</h3>
            <button type="button" class="aksara-popup-close" data-popup-close aria-label="Tutup">&times;</button>
        </div>
        <div class="aksara-popup-body" id="aksaraEditBody"></div>
    </div>
</div>
