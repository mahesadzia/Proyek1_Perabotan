-- ============================================================
-- MIGRATION: Ganti kode rahasia → pertanyaan keamanan
-- Jalankan file ini di phpMyAdmin atau MySQL CLI
-- ============================================================

ALTER TABLE `users`
    ADD COLUMN `security_question` varchar(255) DEFAULT NULL
        COMMENT 'Pertanyaan keamanan pengganti kode rahasia'
        AFTER `secret_code`,
    ADD COLUMN `security_answer` varchar(255) DEFAULT NULL
        COMMENT 'Hash bcrypt jawaban pertanyaan keamanan'
        AFTER `security_question`;

-- Opsional: hapus kolom secret_code yang tidak dipakai lagi
-- ALTER TABLE `users` DROP COLUMN `secret_code`;
-- (beri komentar dulu jika ingin backup kode lama)
