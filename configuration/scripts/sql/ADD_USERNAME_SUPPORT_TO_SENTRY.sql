ALTER TABLE `users` ADD `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL AFTER `id`;
ALTER TABLE `users` ADD CONSTRAINT UNIQUE KEY `users_username_unique` (`username`);