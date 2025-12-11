# 📁 Document Service - E-Arsip Microservices

Service untuk mengelola dokumen arsip (Surat Masuk dan Surat Keluar) dalam sistem e-Arsip P3M.

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Teknologi](#-teknologi)
- [Instalasi](#-instalasi)
- [Konfigurasi](#️-konfigurasi)
- [Menjalankan Service](#-menjalankan-service)
- [API Endpoints](#-api-endpoints)
- [Testing](#-testing)
- [Kontributor](#-kontributor)

---

## 🎯 Fitur Utama

| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| CRUD Dokumen | ✅ | Create, Read, Update, Delete dokumen |
| Upload File | ✅ | Upload PDF, DOC, DOCX |
| Download File | ✅ | Download dokumen yang tersimpan |
| Pencarian | ✅ | Cari berdasarkan nomor surat, perihal |
| Filter | ✅ | Filter berdasarkan type, jenis, tanggal |
| Statistik | ✅ | Dashboard statistik dokumen |
| Pagination | ✅ | Pagination untuk list dokumen |
| Correlation ID | ✅ | Request tracking |

---

## 🛠 Teknologi

- **Framework:** Laravel 10.x
- **PHP:** 8.1+
- **Database:** MySQL 8.0+
- **Authentication:** Laravel Sanctum
- **Testing:** PHPUnit

---

## 📦 Instalasi

### Prasyarat

- PHP >= 8.1
- Composer
- MySQL >= 8.0
- Git

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/ChantikaAurora/e-arsip-microservices-uas.git
cd e-arsip-microservices-uas/document-service

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Buat database
mysql -u root -p -e "CREATE DATABASE document_service;"

# 6. Konfigurasi .env (edit sesuai kebutuhan)
# DB_DATABASE=document_service
# DB_USERNAME=root
# DB_PASSWORD=

# 7. Jalankan migration dan seeder
php artisan migrate --seed

# 8. Setup storage link
php artisan storage:link

## 👥 Kontributor

- **Yola** — Initial Models, Controllers, CRUD Dasar
- **Fathiyyah** - Refactor ke Microservice, File Upload/Download, Search & Filter,
Statistics Endpoint, Middleware Correlation ID, Testing, Documentation


Project ini dibuat untuk keperluan UAS.
