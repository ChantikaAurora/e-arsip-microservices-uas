# Document Service - E-Arsip Microservices

Service untuk mengelola dokumen arsip (Surat Masuk dan Surat Keluar) dalam sistem e-Arsip P3M.

## 📋 Daftar Isi
- [Fitur Utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Menjalankan Service](#menjalankan-service)
- [API Endpoints](#api-endpoints)
- [Testing](#testing)
- [Correlation ID](#correlation-id)

## 🎯 Fitur Utama

- ✅ CRUD Dokumen (Surat Masuk & Surat Keluar)
- ✅ Upload dan Download File (PDF, DOC, DOCX)
- ✅ Pencarian dan Filter Dokumen
- ✅ Statistik Dokumen
- ✅ Pengelompokan berdasarkan Jenis Arsip
- ✅ Correlation ID untuk Request Tracking
- ✅ API RESTful dengan JSON Response

## 🛠 Teknologi

- Laravel 10.x
- PHP 8.1+
- MySQL 8.0+
- Laravel Sanctum (Authentication)
- PHPUnit (Testing)

## 📦 Instalasi

### 1. Clone Repository
```bash
git clone https://github.com/ChantikaAurora/e-arsip-microservices-uas.git
cd e-arsip-microservices-uas/document-service
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Copy Environment File
```bash
cp .env.example .env
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Buat Database
```sql
CREATE DATABASE document_service;
```

### 6. Konfigurasi .env

Edit file `.env` dan sesuaikan konfigurasi database:
```env
DB_DATABASE=document_service
DB_USERNAME=root
DB_PASSWORD=
```

### 7. Jalankan Migration dan Seeder
```bash
php artisan migrate --seed
```

### 8. Setup Storage
```bash
php artisan storage:link
```

## ⚙️ Konfigurasi

### Database

Service ini menggunakan MySQL. Pastikan konfigurasi di `.env` sudah benar:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=document_service
DB_USERNAME=root
DB_PASSWORD=
```

### File Storage

File dokumen disimpan di `storage/app/public/documents`. Pastikan folder ini writable.

## 🚀 Menjalankan Service
```bash
php artisan serve --port=8002
```

Service akan berjalan di `http://localhost:8002`

## 📡 API Endpoints

### Base URL
```
http://localhost:8002/api
```

### Authentication
Semua endpoint memerlukan authentication token (Laravel Sanctum).

Header yang diperlukan:
```
Authorization: Bearer {token}
X-Correlation-ID: {unique-id}
```

### Endpoints

#### 1. List Documents
**GET** `/documents`

Query Parameters:
- `type` (optional): `masuk` atau `keluar`
- `search` (optional): kata kunci pencarian
- `page` (optional): nomor halaman (default: 1)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "type": "masuk",
      "nomor_surat": "001/SM/2024",
      "perihal": "Undangan Rapat",
      "tanggal_surat": "2024-01-15",
      "jenis_arsip": {
        "id": 1,
        "nama": "Surat Masuk"
      }
    }
  ],
  "links": {},
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 18
  }
}
```

#### 2. Create Document
**POST** `/documents`

**Body (multipart/form-data):**
```
type: masuk
nomor_surat: 001/SM/2024
kode_klasifikasi: SK-001
tanggal_surat: 2024-01-15
tanggal_terima: 2024-01-16
asal_surat: Kemendikbud
pengirim: Direktur
perihal: Undangan Rapat
jenis: 1
file: [PDF/DOC/DOCX file]
```

**Response:**
```json
{
  "message": "Document created successfully",
  "document": {
    "id": 1,
    "type": "masuk",
    "nomor_surat": "001/SM/2024",
    "file": "documents/abc123.pdf"
  }
}
```

#### 3. Show Document
**GET** `/documents/{id}`

**Response:**
```json
{
  "id": 1,
  "type": "masuk",
  "nomor_surat": "001/SM/2024",
  "perihal": "Undangan Rapat",
  "file": "documents/abc123.pdf",
  "jenis_arsip": {
    "nama": "Surat Masuk"
  }
}
```

#### 4. Update Document
**PUT** `/documents/{id}`

**Body (JSON):**
```json
{
  "type": "masuk",
  "nomor_surat": "001/SM/2024",
  "perihal": "Undangan Rapat (Updated)"
}
```

#### 5. Delete Document
**DELETE** `/documents/{id}`

**Response:**
```json
{
  "message": "Document deleted successfully"
}
```

#### 6. Download Document
**GET** `/documents/{id}/download`

Returns file stream (PDF/DOC/DOCX)

#### 7. Statistics
**GET** `/documents/stats`

**Response:**
```json
{
  "total_documents": 18,
  "total_surat_masuk": 10,
  "total_surat_keluar": 8,
  "recent_documents": [...]
}
```

## 🧪 Testing

### Menjalankan Test
```bash
php artisan test
```

### Menjalankan Test Spesifik
```bash
php artisan test --filter test_can_upload_document_surat_masuk
```

### Test Coverage

Test yang tersedia:
- ✅ Upload Document
- ✅ Get Statistics
- ✅ List Documents with Pagination
- ✅ Filter by Type
- ✅ Show Single Document
- ✅ Update Document
- ✅ Delete Document
- ✅ Search Documents

## 🔍 Correlation ID

Service ini menggunakan Correlation ID untuk request tracking. Setiap request akan dicatat dengan correlation ID yang unique.

**Cara Menggunakan:**

Tambahkan header `X-Correlation-ID` pada setiap request:
```
X-Correlation-ID: abc-123-def-456
```

Jika tidak disediakan, sistem akan generate otomatis.

**Log Example:**
```
[2024-01-15 10:30:00] local.INFO: Document list requested {"correlation_id":"abc-123"}
[2024-01-15 10:30:01] local.INFO: Document list retrieved {"correlation_id":"abc-123","count":10}
```

## 📄 Struktur Database

### Table: documents

| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| type | enum | 'masuk' atau 'keluar' |
| nomor_surat | varchar | Nomor surat |
| kode_klasifikasi | varchar | Kode klasifikasi |
| tanggal_surat | date | Tanggal surat |
| tanggal_terima | date | Tanggal terima (untuk surat masuk) |
| asal_surat | varchar | Asal surat (untuk surat masuk) |
| tujuan_surat | varchar | Tujuan surat (untuk surat keluar) |
| pengirim | varchar | Nama pengirim |
| penerima | varchar | Nama penerima |
| perihal | varchar | Perihal surat |
| lampiran | varchar | Jumlah lampiran |
| jenis | bigint | Foreign key ke jenisarsips |
| keterangan | text | Keterangan tambahan |
| file | varchar | Path file |
| created_by | bigint | User ID yang membuat |
| created_at | timestamp | Waktu dibuat |
| updated_at | timestamp | Waktu diupdate |

## 👥 Kontributor

- **Yola** — Initial Models, Controllers, CRUD Dasar
- **Fathiyyah** - Refactor ke Microservice, File Upload/Download, Search & Filter,
Statistics Endpoint, Middleware Correlation ID, Testing, Documentation


Project ini dibuat untuk keperluan UAS.
