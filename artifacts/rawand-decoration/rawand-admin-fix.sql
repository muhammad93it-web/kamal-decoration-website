-- ============================================================
--  چاککردنی هەژماری ئەدمین — ڕەوەند دیکۆر
--  ناوی بەکارهێنەر: admin
--  وشەی نهێنی: Rawand@2026
--  ئەم فایلە لە phpMyAdmin لە بەشی SQL بارکە و Go دابگرە
-- ============================================================
DELETE FROM `users` WHERE `username` = 'admin';
INSERT INTO `users` (`username`,`display_name`,`password_hash`,`is_active`) VALUES
('admin','ڕەوەند','$2y$10$UiCekFQpgGuTnX9zawa2uua8NuSVrOVFNzFXgqJl4QDWDP3/gkGci',1);
INSERT INTO `user_roles` (`user_id`,`role_id`)
SELECT `id`, 1 FROM `users` WHERE `username` = 'admin';
