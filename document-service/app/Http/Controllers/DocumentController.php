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
        try {
            // Ambil user yang terautentikasi dari middleware
            $authenticatedUser = $request->get('authenticated_user');

            // Query dengan pagination
            $perPage = $request->input('per_page', 10);
            $documents = Document::with('jenisArsip')
                ->latest()
                ->paginate($perPage);

            Log::info('Documents retrieved', [
                'user_id' => $authenticatedUser['id'] ?? null,
                'user_email' => $authenticatedUser['email'] ?? null,
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
            Log::error('Error retrieving documents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve documents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created document.
     */
    public function store(Request $request)
    {
        try {
            $authenticatedUser = $request->get('authenticated_user');

            // Validasi input
            $validator = Validator::make($request->all(), [
                'nomor_dokumen' => 'required|string|max:100|unique:documents',
                'judul' => 'required|string|max:255',
                'tanggal_dokumen' => 'required|date',
                'jenis_arsip_id' => 'required|exists:jenis_arsips,id',
                'file_path' => 'nullable|string|max:500',
                'keterangan' => 'nullable|string',
            ]);

            if ($validator->fails()) {
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

            Log::info('Document created', [
                'document_id' => $document->id,
                'created_by' => $authenticatedUser['email'] ?? 'Unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document created successfully',
                'data' => $document->load('jenisArsip'),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating document', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified document.
     */
    public function show(Request $request, $id)
    {
        try {
            $document = Document::with('jenisArsip')->find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document retrieved successfully',
                'data' => $document,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified document.
     */
    public function update(Request $request, $id)
    {
        try {
            $authenticatedUser = $request->get('authenticated_user');

            $document = Document::find($id);

            if (!$document) {
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
            ]);

            if ($validator->fails()) {
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

            Log::info('Document updated', [
                'document_id' => $document->id,
                'updated_by' => $authenticatedUser['email'] ?? 'Unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
                'data' => $document->load('jenisArsip'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified document.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $authenticatedUser = $request->get('authenticated_user');

            $document = Document::find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                ], 404);
            }

            $documentInfo = $document->nomor_dokumen;
            $document->delete();

            Log::info('Document deleted', [
                'document_id' => $id,
                'document_number' => $documentInfo,
                'deleted_by' => $authenticatedUser['email'] ?? 'Unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
