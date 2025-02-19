ALTER TABLE `fa_user` 
ADD COLUMN `expire_time` int(10) NULL DEFAULT NULL COMMENT '账号过期时间' AFTER `status`; 