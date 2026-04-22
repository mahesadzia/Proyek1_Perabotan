<?php
// ⚠️ PERINGATAN: Hapus file ini dari server setelah digunakan!
// File ini hanya untuk generate hash password sekali pakai.
$password = password_hash("admin123", PASSWORD_DEFAULT);
echo $password;
?>
