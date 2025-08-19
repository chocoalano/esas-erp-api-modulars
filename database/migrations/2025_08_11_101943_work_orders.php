<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | DESIGN REQUESTS (HEADER)
        |--------------------------------------------------------------------------
        */
        Schema::create('design_requests', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('request_no', 50)->nullable()->unique()->comment('Nomor dokumen, contoh: SAS/FORM/DES/YY/####');
            $table->date('request_date')->comment('Tanggal permintaan dibuat');
            $table->date('need_by_date')->nullable()->comment('Tanggal kebutuhan selesai desain');
            $table->enum('priority', ['high', 'medium', 'low'])->comment('Prioritas pekerjaan: high/medium/low');
            $table->foreignId('pic_id')->constrained('users')->comment('PIC pemohon desain (users.id)');
            $table->foreignId('division_id')->nullable()->constrained('departements')->nullOnDelete()->comment('Divisi pemohon (departements.id)');
            $table->foreignId('submitted_to_id')->nullable()->constrained('users')->nullOnDelete()->comment('Diajukan kepada (umumnya tim/designer)');
            $table->foreignId('acknowledged_by_id')->nullable()->constrained('users')->nullOnDelete()->comment('Diketahui oleh atasan langsung');
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'IN_PROGRESS', 'DONE', 'CANCELED'])
                  ->default('DRAFT')->comment('Status workflow permintaan desain');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->timestamps(); // created_at, updated_at (MySQL timezone-agnostic)
            $table->comment('Header Form Permintaan Pembuatan Desain');
        });

        /*
        |--------------------------------------------------------------------------
        | DESIGN REQUEST ITEMS (DETAIL)
        |--------------------------------------------------------------------------
        */
        Schema::create('design_request_items', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->foreignId('design_request_id')->constrained('design_requests')->cascadeOnDelete()->comment('Relasi ke design_requests.id');
            $table->integer('line_no')->comment('Nomor urut baris pada form');
            $table->text('kebutuhan')->comment('Jenis kebutuhan desain');
            $table->text('isi_konten')->nullable()->comment('Isi konten/teks yang harus dimasukkan');
            $table->string('ukuran', 100)->nullable()->comment('Ukuran/format output (mis. A4, 1080x1350)');
            $table->text('referensi')->nullable()->comment('Referensi/link rujukan (file di attachments)');
            $table->text('keterangan')->nullable()->comment('Keterangan tambahan');
            $table->timestamps();

            $table->unique(['design_request_id', 'line_no'], 'dri_unique_line');
            $table->comment('Detail kebutuhan untuk setiap permintaan desain');
        });

        /*
        |--------------------------------------------------------------------------
        | DESIGN APPROVALS
        |--------------------------------------------------------------------------
        */
        Schema::create('design_approvals', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->foreignId('design_request_id')->constrained('design_requests')->cascadeOnDelete()->comment('Relasi ke design_requests.id');
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete()->comment('Pegawai/approver yang memutuskan');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING')->comment('Status persetujuan');
            $table->timestamp('decided_at')->nullable()->comment('Waktu keputusan dibuat');
            $table->text('remarks')->nullable()->comment('Catatan/pertimbangan keputusan');
            $table->timestamps();

            $table->index(['design_request_id', 'status'], 'design_approvals_status_idx');
            $table->comment('Histori persetujuan permintaan desain');
        });

        /*
        |--------------------------------------------------------------------------
        | WORK ORDERS (HEADER)
        |--------------------------------------------------------------------------
        */
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('wo_no',50)->nullable()->unique()->comment('Nomor WO, contoh: SAS/FORM/MTC/YY/####');
            $table->foreignId('requested_by_id')->constrained('users')->comment('Pemohon WO (users.id)');
            $table->date('request_date')->comment('Tanggal WO diajukan');
            $table->foreignId('department_provides')->nullable()->constrained('departements')->nullOnDelete()->comment('Departemen yg melayani');
            $table->foreignId('department_id')->nullable()->constrained('departements')->nullOnDelete()->comment('Departemen pemohon');
            $table->string('area',150)->nullable()->comment('Area/lokasi pekerjaan');
            $table->text('complaint')->nullable()->comment('Keluhan/masalah yang dilaporkan');
            $table->string('asset_info')->nullable()->comment('Info mesin/sarana singkat (jika tanpa master asset)');
            $table->enum('status', ['OPEN','IN_PROGRESS','ON_HOLD','DONE','CANCELED'])->default('OPEN')->comment('Status WO');
            $table->timestamp('start_time')->nullable()->comment('Tanggal-waktu mulai dikerjakan');
            $table->timestamp('end_time')->nullable()->comment('Tanggal-waktu pekerjaan selesai');
            $table->timestamps();

            $table->index(['status','request_date'], 'work_orders_status_date_idx');
            $table->comment('Header Form Work Order (Maintenance)');
        });

        /*
        |--------------------------------------------------------------------------
        | WO SERVICES (LOG PEKERJAAN)
        |--------------------------------------------------------------------------
        */
        Schema::create('wo_services', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete()->comment('Relasi ke work_orders.id');
            $table->text('description')->comment('Deskripsi pekerjaan/servis yang dilakukan');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete()->comment('Pencatat/teknisi yang membuat log');
            $table->timestamp('created_at')->useCurrent()->comment('Waktu catatan dibuat');
            // tidak pakai updated_at agar log immutable

            $table->comment('Log/daftar servis yang dilakukan pada WO');
        });

        /*
        |--------------------------------------------------------------------------
        | WO SPAREPARTS
        |--------------------------------------------------------------------------
        */
        Schema::create('wo_spareparts', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete()->comment('Relasi ke work_orders.id');
            $table->string('part_name',150)->comment('Nama sparepart digunakan');
            $table->decimal('quantity',12,2)->default(1)->comment('Jumlah sparepart');
            $table->text('remarks')->nullable()->comment('Keterangan tambahan');
            $table->timestamps();

            $table->index(['work_order_id','part_name'], 'wo_spareparts_idx');
            $table->comment('Daftar sparepart yang digunakan pada WO');
        });

        /*
        |--------------------------------------------------------------------------
        | WO CLEARANCES
        |--------------------------------------------------------------------------
        */
        Schema::create('wo_clearances', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete()->comment('Relasi ke work_orders.id');
            $table->boolean('hygiene_clearance')->nullable()->comment('Hygiene Clearance: 1=Ya, 0=Tidak, null=Tidak diisi');
            $table->boolean('maintenance_clearance')->nullable()->comment('Maintenance Clearance: 1=Ya, 0=Tidak, null=Tidak diisi');
            $table->foreignId('verified_by_id')->nullable()->constrained('users')->nullOnDelete()->comment('Pegawai yang memverifikasi');
            $table->timestamp('verified_at')->nullable()->comment('Waktu verifikasi dilakukan');
            $table->timestamps();

            $table->comment('Pernyataan clearance dan verifikasi pada WO');
        });

        /*
        |--------------------------------------------------------------------------
        | WO SIGNOFFS
        |--------------------------------------------------------------------------
        */
        Schema::create('wo_signoffs', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete()->comment('Relasi ke work_orders.id');
            $table->foreignId('done_by_id')->nullable()->constrained('users')->nullOnDelete()->comment('Pekerjaan diselesaikan oleh (teknisi)');
            $table->foreignId('head_maintenance_id')->nullable()->constrained('users')->nullOnDelete()->comment('Disetujui Head Maintenance');
            $table->foreignId('requester_verify_id')->nullable()->constrained('users')->nullOnDelete()->comment('Verifikasi pemohon');
            $table->text('notes')->nullable()->comment('Catatan penyerahan/penyelesaian');
            $table->timestamp('signed_at')->nullable()->comment('Waktu penandatanganan/verifikasi');
            $table->timestamps();

            $table->comment('Tanda tangan penyelesaian dan verifikasi WO');
        });

        /*
        |--------------------------------------------------------------------------
        | ATTACHMENTS (POLYMORPHIC)
        |--------------------------------------------------------------------------
        */
        Schema::create('attachments', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('owner_type', 50)->comment('Tipe pemilik lampiran, mis: DESIGN_REQUEST atau WORK_ORDER');
            $table->unsignedBigInteger('owner_id')->comment('ID entity pemilik lampiran');
            $table->string('file_name',255)->nullable()->comment('Nama file asli');
            $table->string('file_path',500)->nullable()->comment('Path penyimpanan lokal');
            $table->text('url')->nullable()->comment('URL (S3/Drive/dll) jika tidak lokal');
            $table->json('meta')->nullable()->comment('Metadata tambahan (mime, size, dll)');
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete()->comment('Uploader lampiran');
            $table->timestamp('uploaded_at')->useCurrent()->comment('Waktu upload');
            $table->timestamps();

            $table->index(['owner_type','owner_id'], 'attachments_owner_idx');
            $table->comment('Lampiran file/URL untuk berbagai entitas');
        });

        /*
        |--------------------------------------------------------------------------
        | STATUS HISTORIES
        |--------------------------------------------------------------------------
        */
        Schema::create('status_histories', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('owner_type', 50)->comment('Tipe entitas: DESIGN_REQUEST atau WORK_ORDER');
            $table->unsignedBigInteger('owner_id')->comment('ID entitas pemilik histori');
            $table->string('from_status',30)->nullable()->comment('Status sebelum perubahan');
            $table->string('to_status',30)->comment('Status sesudah perubahan');
            $table->foreignId('changed_by_id')->nullable()->constrained('users')->nullOnDelete()->comment('User/pegawai yang melakukan perubahan');
            $table->timestamp('changed_at')->useCurrent()->comment('Waktu perubahan status');
            $table->text('remarks')->nullable()->comment('Catatan alasan perubahan');
            $table->timestamps();

            $table->index(['owner_type','owner_id','changed_at'], 'status_histories_owner_idx');
            $table->comment('Riwayat perubahan status entitas');
        });

        /*
        |--------------------------------------------------------------------------
        | (Opsional) Check konsistensi waktu WO untuk MySQL
        |--------------------------------------------------------------------------
        | MySQL tidak punya CHECK yang aktif di versi lama; untuk 8.0+ bisa dipakai.
        */
        Schema::table('work_orders', function (Blueprint $table) {
            // Jika MySQL 8.0+, aktifkan baris di bawah:
            // $table->check("(end_time IS NULL OR start_time IS NULL OR end_time >= start_time)", 'work_orders_time_ck');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_approvals');
        Schema::dropIfExists('design_request_items');
        Schema::dropIfExists('design_requests');

        Schema::dropIfExists('wo_signoffs');
        Schema::dropIfExists('wo_clearances');
        Schema::dropIfExists('wo_spareparts');
        Schema::dropIfExists('wo_services');
        Schema::dropIfExists('work_orders');

        Schema::dropIfExists('status_histories');
        Schema::dropIfExists('attachments');
    }
};
