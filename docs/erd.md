# Entity Relationship Diagram (ERD) — ALDI-MOTOR
**Platform Reservasi dan Manajemen Servis Bengkel UMKM**
*GTNIC 2026 — Web Development Competition*

---

## 1. Diagram ERD (Mermaid)

```mermaid
erDiagram
    USERS ||--o{ VEHICLES : "memiliki"
    USERS ||--o{ BOOKINGS : "membuat"
    USERS ||--o{ BOOKING_STATUS_LOGS : "mengubah_status"
    USERS ||--o{ NOTIFICATION_LOGS : "mengirim_notifikasi"

    VEHICLES ||--o{ BOOKINGS : "diservis_dalam"
    SERVICES ||--o{ BOOKINGS : "dipilih_dalam"

    BOOKINGS ||--o{ BOOKING_STATUS_LOGS : "memiliki_riwayat"
    BOOKINGS ||--o{ NOTIFICATION_LOGS : "memiliki_log_pesan"

    USERS {
        bigint id PK
        string name
        string email UK
        string phone UK "Format 628xxx"
        string role "Enum: customer, admin"
        boolean whatsapp_opt_in
        int no_show_count "Default 0"
        datetime blocked_until "Nullable"
        boolean aktif "Default true"
        string password
        datetime created_at
        datetime updated_at
    }

    VEHICLES {
        bigint id PK
        bigint user_id FK
        string plate_number
        string brand "Contoh: Honda, Yamaha"
        string model "Contoh: Vario 160, NMAX"
        smallint year
        datetime deleted_at "Soft Deletes"
        datetime created_at
        datetime updated_at
    }

    SERVICES {
        bigint id PK
        string name
        text description
        smallint duration_minutes "Estimasi pengerjaan"
        bigint price_estimate "Estimasi biaya IDR"
        smallint reminder_interval_days "Siklus pengingat servis"
        boolean is_active "Default true"
        datetime created_at
        datetime updated_at
    }

    WORKSHOP_SETTINGS {
        bigint id PK
        string key UK "Contoh: daily_quota"
        text value
        string type "integer, boolean, string, json"
        datetime created_at
        datetime updated_at
    }

    DAILY_CAPACITIES {
        bigint id PK
        date date UK
        smallint quota "Nullable (override kuota)"
        boolean is_open "Default true (false = libur)"
        string note "Catatan operasional"
        datetime created_at
        datetime updated_at
    }

    BOOKINGS {
        bigint id PK
        string booking_code UK "Format: AM-YYYYMMDD-XXX"
        bigint user_id FK
        bigint vehicle_id FK
        bigint service_id FK
        date booking_date
        smallint queue_order "Nomor antrean harian"
        time estimated_arrival_at "Jam kedatangan"
        string status "Enum: pending, confirmed, dll"
        smallint active_queue_order "Stored Generated (NULL jika cancelled)"
        text complaint "Keluhan awal"
        text cancel_reason "Alasan pembatalan"
        datetime checked_in_at
        datetime started_at
        datetime finished_at
        datetime cancelled_at
        datetime created_at
        datetime updated_at
    }

    BOOKING_STATUS_LOGS {
        bigint id PK
        bigint booking_id FK
        string from_status "Nullable"
        string to_status
        bigint changed_by FK "Nullable"
        text note "Catatan perubahan"
        datetime created_at
    }

    NOTIFICATION_LOGS {
        bigint id PK
        bigint booking_id FK "Nullable"
        string type "booking_confirmation, reminder"
        string channel "Default: whatsapp"
        string phone "Nomor penerima 628xxx"
        text message "Isi pesan WA"
        datetime opened_at "Nullable"
        bigint sent_by FK "Nullable"
        datetime created_at
        datetime updated_at
    }
```

---

## 2. Penjelasan Pilihan Index dan Constraint Arsitektural

1. **Composite Unique Constraint pada Kendaraan**: Kami menerapkan kombinasi `UNIQUE (user_id, plate_number)` pada tabel `vehicles` guna mencegah duplikasi pencatatan plat nomor oleh pemilik yang sama tanpa mengunci hak registrasi ulang jika kendaraan berpindah kepemilikan ke pengguna lain.
2. **Partial Unique Constraint Antrean via Stored Generated Column**: Kolom *stored generated* `active_queue_order` yang hanya bernilai `queue_order` jika status bukan `cancelled` memungkinkan constraint `UNIQUE (booking_date, active_queue_order)` berjalan secara parsial di MySQL, sehingga slot antrean yang dibatalkan dapat dialokasikan ulang tanpa melanggar integritas unik.
3. **Composite Index Performa Kuota dan Tanggal**: Index komposit pada `(booking_date, status)` diterapkan pada tabel `bookings` untuk mempercepat query agregasi perhitungan kuota harian yang dieksekusi secara intensif setiap kali pengguna memeriksa ketersediaan slot tanpa melakukan *full table scan*.
4. **Integritas Foreign Key Bertingkat**: Relasi *foreign key* dikonfigurasi dengan aturan terukur: penghapusan akun `users` akan memicu `cascade` pada kendaraan miliknya, sedangkan penghapusan master `services` dibatasi (`restrict`) agar seluruh histori transaksi servis terdahulu tidak mengalami kerusakan integritas data (*orphaned records*).
5. **Audit Trail & Logging Terisolasi**: Tabel `booking_status_logs` dan `notification_logs` diindeks pada `booking_id` untuk memastikan penelusuran riwayat status pengerjaan dan log komunikasi WhatsApp berlangsung cepat dalam hitungan milidetik saat dievaluasi oleh mekanik maupun administrator bengkel.
