-- 卡密表
CREATE TABLE `fa_card` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `card_no` varchar(32) NOT NULL COMMENT '卡密号码',
    `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '卡密类型:1=月卡,2=季卡,3=年卡',
    `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '使用状态:0=未使用,1=已使用',
    `creator_id` int(11) NOT NULL COMMENT '生成者ID',
    `creator_type` tinyint(1) NOT NULL COMMENT '生成者类型:1=管理员,2=高级代理',
    `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `card_no` (`card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡密表';

-- 卡密使用记录表
CREATE TABLE `fa_card_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `card_id` int(11) NOT NULL COMMENT '卡密ID',
    `user_id` int(11) NOT NULL COMMENT '使用者ID',
    `use_time` int(10) DEFAULT NULL COMMENT '使用时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡密使用记录'; 