// 在安装脚本中添加以下表结构
$sql = <<<EOF
CREATE TABLE IF NOT EXISTS `fa_card` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL COMMENT '代理ID',
  `card_no` varchar(50) NOT NULL COMMENT '卡密',
  `type` tinyint(1) NOT NULL COMMENT '卡密类型 1:7天 2:30天 3:90天',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0:未使用 1:已使用',
  `createtime` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_no` (`card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡密表';

CREATE TABLE IF NOT EXISTS `fa_card_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card_id` int(11) NOT NULL COMMENT '卡密ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `usetime` int(11) NOT NULL COMMENT '使用时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡密使用记录表';
EOF; 