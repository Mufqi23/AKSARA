<?php

namespace App\Http\Controllers\Dataset;

use App\Http\Controllers\Controller;
use App\Services\Access;
use App\Services\Dataset\DatasetImportRunner;
use App\Services\Dataset\DatasetRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DatasetImportController extends Controller
{
    public function showPreview(Request $request)
    {
        $user = $request->user();
        abort_unless(DatasetRegistry::canOperate($user), 403);

        $preview = $request->session()->get('dataset_preview');
        if (! is_array($preview) || empty($preview['module'])) {
            return redirect('/dataset')->withErrors(['file' => 'Belum ada sesi pratinjau berkas impor yang aktif.']);
        }

        abort_unless(DatasetRegistry::canAccessModule($user, (string) $preview['module'], 'import'), 403);

        $moduleTitle = $preview['module'] === 'master_bundle'
            ? 'Paket Lengkap Data Sekolah (7 Lembar)'
            : (DatasetRegistry::modules()[$preview['module']]['title'] ?? $preview['module']);

        return view('dataset.preview', [
            'user' => $user,
            'preview' => $preview,
            'moduleTitle' => $moduleTitle,
        ]);
    }

    public function preview(Request $request)
    {
        $user = $request->user();
        abort_unless(DatasetRegistry::canOperate($user), 403);

        $validated = $request->validate([
            'module' => ['required', 'string', 'max:60'],
            'class_id' => ['nullable', 'integer'],
            'file' => ['required', 'file', 'max:5120'],
        ], [
            'file.required' => 'Pilih berkas dataset (.xlsx atau .csv) terlebih dahulu.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $module = (string) $validated['module'];
        abort_unless(DatasetRegistry::canAccessModule($user, $module, 'import'), 403);

        $classId = ! empty($validated['class_id']) ? (int) $validated['class_id'] : null;
        if ($classId !== null) {
            abort_unless(Access::classes($user)->whereKey($classId)->exists(), 403);
        }

        $preview = DatasetImportRunner::previewUploaded($user, $module, $request->file('file'), $classId);
        $preview['token'] = Str::random(40);
        $preview['generated_at'] = now()->format('d M Y, H:i');

        $request->session()->put('dataset_preview', $preview);

        return redirect('/dataset/preview');
    }

    public function commit(Request $request)
    {
        $user = $request->user();
        abort_unless(DatasetRegistry::canOperate($user), 403);

        $preview = $request->session()->get('dataset_preview');
        if (! is_array($preview) || ! hash_equals((string) ($preview['token'] ?? ''), (string) $request->input('preview_token', ''))) {
            return redirect('/dataset')->withErrors(['file' => 'Sesi pratinjau telah kedaluwarsa. Silakan unggah ulang berkas Anda.']);
        }

        if (! empty($preview['errors'])) {
            return redirect('/dataset/preview')->withErrors(['file' => 'Perbaiki baris yang masih bermasalah pada berkas sebelum menyimpan data.']);
        }

        $result = DatasetImportRunner::commitPayload($user, $preview, $request->ip());
        $classId = $preview['class_id'] ?? null;
        $request->session()->forget('dataset_preview');

        $target = $classId ? '/dataset?class_id='.(int) $classId : '/dataset';

        return redirect($target)->with(
            'success',
            "Data berhasil diimpor: {$result['created']} data baru ditambahkan dan {$result['updated']} data diperbarui."
        );
    }

    public function cancel(Request $request)
    {
        abort_unless(DatasetRegistry::canOperate($request->user()), 403);
        $classId = $request->session()->get('dataset_preview.class_id');
        $request->session()->forget('dataset_preview');

        return redirect($classId ? '/dataset?class_id='.(int) $classId : '/dataset')
            ->with('success', 'Pratinjau impor dibatalkan. Belum ada data yang diubah.');
    }
}
