<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::with('jenis')->get();

        return response()->json([
            'message' => 'Documents retrieved successfully',
            'data' => $documents
        ]);
    }
    public function show($id)
    {
        $document = Document::with('jenis')->findOrFail($id);

        return response()->json([
            'message' => 'Document retrieved successfully',
            'data' => $document
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:masuk,keluar',
            'nomor_surat' => 'required',
            'kode_klasifikasi' => 'required',
            'tanggal_surat' => 'required|date',
            'tanggal_terima' => 'nullable|date',
            'asal_surat' => 'nullable',
            'tujuan_surat' => 'nullable',
            'pengirim' => 'nullable',
            'penerima' => 'nullable',
            'file' => 'nullable|file|mimes:pdf,doc,docx',
            'jenis_id' => 'required|exists:jenis_arsips,id',
        ]);

        if ($request->hasFile('file')) {
            $validated['file'] = $request->file('file')->store('documents', 'public');
        }

        $validated['created_by'] = Auth::id();

        $document = Document::create($validated);

        return response()->json([
            'message' => 'Document created successfully',
            'data' => $document
        ]);
    }
    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        $validated = $request->validate([
            'nomor_surat' => 'required',
            'kode_klasifikasi' => 'required',
            'tanggal_surat' => 'required|date',
            'tanggal_terima' => 'nullable|date',
            'asal_surat' => 'nullable',
            'tujuan_surat' => 'nullable',
            'pengirim' => 'nullable',
            'penerima' => 'nullable',
            'file' => 'nullable|file|mimes:pdf,doc,docx',
            'jenis_id' => 'required|exists:jenis_arsips,id',
        ]);

        if ($request->hasFile('file')) {
            if ($document->file && Storage::disk('public')->exists($document->file)) {
                Storage::disk('public')->delete($document->file);
            }

            $validated['file'] = $request->file('file')->store('documents', 'public');
        }

        $document->update($validated);

        return response()->json([
            'message' => 'Document updated successfully',
            'data' => $document
        ]);
    }
    public function destroy($id)
    {
        $document = Document::findOrFail($id);

        // Hapus file jika ada
        if ($document->file && Storage::disk('public')->exists($document->file)) {
            Storage::disk('public')->delete($document->file);
        }

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully'
        ]);
    }
    public function download($id)
    {
        $document = Document::findOrFail($id);

        if (!$document->file || !Storage::disk('public')->exists($document->file)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($document->file);
    }
}
