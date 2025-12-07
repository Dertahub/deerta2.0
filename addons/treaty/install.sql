CREATE TABLE IF NOT EXISTS `__PREFIX__treaty_info`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) NULL DEFAULT NULL COMMENT '协议id',
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `phone` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '手机号',
  `id_card` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '身份证号',
  `image` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '签名',
  `images` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '签名信息',
  `code` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '唯一code',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
  `createtime` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT 0 COMMENT '权重',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `weigh`(`weigh`, `id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '协议详情表';

SET FOREIGN_KEY_CHECKS = 1;
CREATE TABLE IF NOT EXISTS `__PREFIX__treaty_category`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '协议名称',
  `image` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '图片',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '协议内容',
  `signature` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '签名键值对',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `createtime` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT 0 COMMENT '权重',
  `jump_type` int(1) NOT NULL DEFAULT 1 COMMENT '跳转类型',
  `jump_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '跳转链接',
  `official_seal_image` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '公章图片',
  `check_login` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否登入',
  `check_repeat` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否重复提交',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `weigh`(`weigh`, `id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '协议表';

SET FOREIGN_KEY_CHECKS = 1;


-- 1.0.2新增签名键值字段
ALTER TABLE `__PREFIX__treaty_category` ADD COLUMN `signature` text NULL DEFAULT NULL COMMENT '签名键值对' AFTER `content`;
ALTER TABLE `__PREFIX__treaty_category` ADD COLUMN `jump_type` int(1) NOT NULL DEFAULT 1 COMMENT '跳转类型';
ALTER TABLE `__PREFIX__treaty_category` ADD COLUMN `jump_url` varchar(255) NOT NULL DEFAULT "" COMMENT '跳转链接' AFTER `jump_type`;
ALTER TABLE `__PREFIX__treaty_category` ADD COLUMN `official_seal_image` varchar(255) NOT NULL DEFAULT "" COMMENT '公章图片' AFTER `jump_url`;
ALTER TABLE `__PREFIX__treaty_category` ADD COLUMN `check_login` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否登入' AFTER `official_seal_image`;
ALTER TABLE `__PREFIX__treaty_category` ADD COLUMN `check_repeat` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否重复提交' AFTER `check_login`;
ALTER TABLE `__PREFIX__treaty_info` ADD COLUMN `images` text NULL DEFAULT NULL COMMENT '签名信息' AFTER `image`;
ALTER TABLE `__PREFIX__treaty_info` ADD COLUMN `code` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一code' AFTER `images`;
ALTER TABLE `__PREFIX__treaty_info` ADD COLUMN `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id' AFTER `code`;