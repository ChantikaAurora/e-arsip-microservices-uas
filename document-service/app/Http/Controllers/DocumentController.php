<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\JenisArsip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    /**
     * Display a listing of the documents.
     */
    public function index(Request $request)
    {
        // ✅ Get Correlation ID
        $correlationId = $request->attributes->get('correlation_id');

        try {
            // Ambil user yang terautentikasi dari middleware
            $authenticatedUser = $request->get('authenticated_user');

            // ✅ Logging dengan Correlation ID
            Log::info('Documents list requested', [
                'correlation_id' => $correlationId,
                'user_id' => $authenticatedUser['id'] ?? null,
                'user_email' => $authenticatedUser['email'] ?? null,
            ]);

            // Query dengan pagination
            $perPage = $request->input('per_page', 10);
            $documents = Document::with('jenisArsip')
                ->latest()
                ->paginate($perPage);

            // ✅ Logging success dengan Correlation ID
            Log::info('Documents retrieved successfully', [
                'correlation_id' => $correlationId,
                'total' => $documents->total(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documents retrieved successfully',
                'user' => [
                    'name' => $authenticatedUser['name'] ?? 'Unknown',
                    'email' => $authenticatedUser['email'] ?? 'Unknown',
                ],
                'data' => $documents,
            ], 200);

        } catch (\Exception $e) {
            // ✅ Logging error dengan Correlation ID
            Log::error('Error retrieving documents', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve documents',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * Store a newly created document.
     */
    public function store(Request $request)
    {
        // ✅ Get Correlation ID
        $correlationId = $request->attributes->get('correlation_id');

        try {
            $authenticatedUser = $request->get('authenticated_user');

            // ✅ Logging dengan Correlation ID
            Log::info('Create document attempt', [
                'correlation_id' => $correlationId,
                'user_id' => $authenticatedUser['id'] ?? null,
            ]);

            // Validasi input
            $validator = Validator::make($request->all(), [
                'nomor_dokumen' => 'required|string|max:100|unique:documents',
                'judul' => 'required|string|max:255',
                'tanggal_dokumen' => 'required|date',
                'jenis_arsip_id' => 'required|exists:jenis_arsips,id',
                'file_path' => 'nullable|string|max:500',
                'keterangan' => 'nullable|string',
            ], [
                'nomor_dokumen.required' => 'Nomor dokumen wajib diisi',
                'nomor_dokumen.unique' => 'Nomor dokumen sudah ada',
                'judul.required' => 'Judul wajib diisi',
                'tanggal_dokumen.required' => 'Tanggal dokumen wajib diisi',
                'jenis_arsip_id.required' => 'Jenis arsip wajib dipilih',
                'jenis_arsip_id.exists' => 'Jenis arsip tidak valid',
            ]);

            if ($validator->fails()) {
                // ✅ Logging validation failed dengan Correlation ID
                Log::warning('Document creation validation failed', [
                    'correlation_id' => $correlationId,
                    'errors' => $validator->errors(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Create document
            $document = Document::create([
                'nomor_dokumen' => $request->nomor_dokumen,
                'judul' => $request->judul,
                'tanggal_dokumen' => $request->tanggal_dokumen,
                'jenis_arsip_id' => $request->jenis_arsip_id,
                'file_path' => $request->file_path,
                'keterangan' => $request->keterangan,
                'created_by' => $authenticatedUser['id'] ?? null,
            ]);

            // ✅ Logging success dengan Correlation ID
            Log::info('Document created successfully', [
                'correlation_id' => $correlationId,
                'document_id' => $document->id,
                'created_by' => $authenticatedUser['email'] ?? 'Unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document created successfully',
                'data' => $document->load('jenisArsip'),
            ], 201);

        } catch (\Exception $e) {
            // ✅ Logging error dengan Correlation ID
            Log::error('Error creating document', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create document',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * Display the specified document.
     */
    public function show(Request $request, $id)
    {
        // ✅ Get Correlation ID
        $correlationId = $request->attributes->get('correlation_id');

        try {
            // ✅ Logging dengan Correlation ID
            Log::info('Document detail requested', [
                'correlation_id' => $correlationId,
                'document_id' => $id,
            ]);

            $document = Document::with('jenisArsip')->find($id);

            if (!$document) {
                // ✅ Logging not found dengan Correlation ID
                Log::warning('Document not found', [
                    'correlation_id' => $correlationId,
                    'document_id' => $id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                ], 404);
            }

            // ✅ Logging success dengan Correlation ID
            Log::info('Document retrieved successfully', [
                'correlation_id' => $correlationId,
                'document_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document retrieved successfully',
                'data' => $document,
            ], 200);

        } catch (\Exception $e) {
            // ✅ Logging error dengan Correlation ID
            Log::error('Error retrieving document', [
                'correlation_id' => $correlationId,
                'document_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve document',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * Update the specified document.
     */
    public function update(Request $request, $id)
    {
        // ✅ Get Correlation ID
        $correlationId = $request->attributes->get('correlation_id');

        try {
            $authenticatedUser = $request->get('authenticated_user');

            // ✅ Logging dengan Correlation ID
            Log::info('Update document attempt', [
                'correlation_id' => $correlationId,
                'document_id' => $id,
                'updated_by' => $authenticatedUser['id'] ?? null,
            ]);

            $document = Document::find($id);

            if (!$document) {
                // ✅ Logging not found dengan Correlation ID
                Log::warning('Document not found', [
                    'correlation_id' => $correlationId,
                    'document_id' => $id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nomor_dokumen' => 'sometimes|string|max:100|unique:documents,nomor_dokumen,' . $id,
                'judul' => 'sometimes|string|max:255',
                'tanggal_dokumen' => 'sometimes|date',
                'jenis_arsip_id' => 'sometimes|exists:jenis_arsips,id',
                'file_path' => 'nullable|string|max:500',
                'keterangan' => 'nullable|string',
            ], [
                'nomor_dokumen.unique' => 'Nomor dokumen sudah ada',
                'tanggal_dokumen.date' => 'Format tanggal tidak valid',
                'jenis_arsip_id.exists' => 'Jenis arsip tidak valid',
            ]);

            if ($validator->fails()) {
                // ✅ Logging validation failed dengan Correlation ID
                Log::warning('Document update validation failed', [
                    'correlation_id' => $correlationId,
                    'errors' => $validator->errors(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $document->update($request->only([
                'nomor_dokumen',
                'judul',
                'tanggal_dokumen',
                'jenis_arsip_id',
                'file_path',
                'keterangan',
            ]));

            // ✅ Logging success dengan Correlation ID
            Log::info('Document updated successfully', [
                'correlation_id' => $correlationId,
                'document_id' => $document->id,
                'updated_by' => $authenticatedUser['email'] ?? 'Unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
                'data' => $document->load('jenisArsip'),
            ], 200);

        } catch (\Exception $e) {
            // ✅ Logging error dengan Correlation ID
            Log::error('Error updating document', [
                'correlation_id' => $correlationId,
                'document_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update document',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    /**
     * Remove the specified document.
     */
    public function destroy(Request $request, $id)
    {
        // ✅ Get Correlation ID
        $correlationId = $request->attributes->get('correlation_id');

        try {
            $authenticatedUser = $request->get('authenticated_user');

            // ✅ Logging dengan Correlation ID
            Log::info('Delete document attempt', [
                'correlation_id' => $correlationId,
                'document_id' => $id,
                'deleted_by' => $authenticatedUser['id'] ?? null,
            ]);

            $document = Document::find($id);

            if (!$document) {
                // ✅ Logging not found dengan Correlation ID
                Log::warning('Document not found', [
                    'correlation_id' => $correlationId,
                    'document_id' => $id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                ], 404);
            }

            $documentInfo = $document->nomor_dokumen;
            $document->delete();

            // ✅ Logging success dengan Correlation ID
            Log::info('Document deleted successfully', [
                'correlation_id' => $correlationId,
                'document_id' => $id,
                'document_number' => $documentInfo,
                'deleted_by' => $authenticatedUser['email'] ?? 'Unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            // ✅ Logging error dengan Correlation ID
            Log::error('Error deleting document', [
                'correlation_id' => $correlationId,
                'document_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => 'Terjadi kesalahan server',
            ], 500);
        }
    }
}
