-- ============================================================
--  چاککردنی هەژماری ئەدمین — کەمال دیکۆر
--  ناوی بەکارهێنەر: admin
--  وشەی نهێنی: Kamal@2026
--  ئەم فایلە لە phpMyAdmin لە بەشی SQL بارکە و Go دابگرە
-- ============================================================
DELETE FROM `users` WHERE `username` = 'admin';
INSERT INTO `users` (`username`,`display_name`,`password_hash`,`is_active`) VALUES
('admin','کەمال','$2y$10$B2mydBlSs.HqQg43Zt9lKu3I6NxUjq0MIPFTbjS781ffXRiO5yjCm',1);
INSERT INTO `user_roles` (`user_id`,`role_id`)
SELECT `id`, 1 FROM `users` WHERE `username` = 'admin';
