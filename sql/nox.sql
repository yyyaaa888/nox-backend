/*
 Navicat Premium Dump SQL

 Source Server         : local
 Source Server Type    : MySQL
 Source Server Version : 50726 (5.7.26)
 Source Host           : localhost:3306
 Source Schema         : nox

 Target Server Type    : MySQL
 Target Server Version : 50726 (5.7.26)
 File Encoding         : 65001

 Date: 16/09/2026 16:30:57
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for admin_extension_histories
-- ----------------------------
DROP TABLE IF EXISTS `admin_extension_histories`;
CREATE TABLE `admin_extension_histories`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` tinyint(4) NOT NULL DEFAULT 1,
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `detail` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `admin_extension_histories_name_index`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 104 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_extension_histories
-- ----------------------------
INSERT INTO `admin_extension_histories` VALUES (1, 'dcat-admin.operation-log', 2, '1.0.0', 'create_opration_log_table.php', '2025-08-12 22:04:27', '2025-08-12 22:04:27');
INSERT INTO `admin_extension_histories` VALUES (2, 'dcat-admin.operation-log', 1, '1.0.0', 'Initialize extension.', '2025-08-12 22:06:26', '2025-08-12 22:06:26');
INSERT INTO `admin_extension_histories` VALUES (3, 'guanguans.dcat-login-captcha', 1, '1.0.0', 'Initial release.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (4, 'guanguans.dcat-login-captcha', 1, '1.0.1', 'Add default config file.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (5, 'guanguans.dcat-login-captcha', 1, '1.0.1', 'Add annotation for facades.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (6, 'guanguans.dcat-login-captcha', 1, '1.0.1', 'Optimize `login_captcha_check` function.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (7, 'guanguans.dcat-login-captcha', 1, '1.0.1', 'Optimize captcha generate.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (8, 'guanguans.dcat-login-captcha', 1, '1.0.1', 'Optimize get setting config.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (9, 'guanguans.dcat-login-captcha', 1, '1.0.1', 'Rename `dcat_login_captcha_check`->`login_captcha_check`.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (10, 'guanguans.dcat-login-captcha', 1, '1.0.1', 'Rename `dcat_login_captcha_url`->`login_captcha_url`.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (11, 'guanguans.dcat-login-captcha', 1, '1.0.2', 'Add login_captcha_get function.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (12, 'guanguans.dcat-login-captcha', 1, '1.0.2', 'Update lang files.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (13, 'guanguans.dcat-login-captcha', 1, '1.0.2', 'Update extension alias and description.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (14, 'guanguans.dcat-login-captcha', 1, '1.0.2', 'Optimize LoginCaptchaServiceProvider.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (15, 'guanguans.dcat-login-captcha', 1, '1.0.2', 'Optimize setting form.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (16, 'guanguans.dcat-login-captcha', 1, '1.0.3', 'Add CleanObContents Middleware.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (17, 'guanguans.dcat-login-captcha', 1, '1.0.4', 'Add SetResponseContentType Middleware.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (18, 'guanguans.dcat-login-captcha', 1, '1.0.4', 'Add content type setting config.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (19, 'guanguans.dcat-login-captcha', 1, '1.0.5', 'Add BootingHandler.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (20, 'guanguans.dcat-login-captcha', 1, '1.0.6', 'Rename src/BootingAdmin.php -> src/BootingHandler.php.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (21, 'guanguans.dcat-login-captcha', 1, '1.0.6', 'Remove src/Http/Controllers/CaptchaController.php`.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (22, 'guanguans.dcat-login-captcha', 1, '1.0.7', 'Optimize `buildCaptchaJsScript`.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (23, 'guanguans.dcat-login-captcha', 1, '1.0.8', 'Fix cant match routing path(#8).', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (24, 'guanguans.dcat-login-captcha', 1, '1.0.9', 'Add parameters to the `SetResponseContentType` middleware.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (25, 'guanguans.dcat-login-captcha', 1, '1.0.9', 'Update github config files.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (26, 'guanguans.dcat-login-captcha', 1, '1.0.9', 'Update phpunit/phpunit requirement from ^7.0 || ^8.0 to ^7.0 || ^8.0 || ^9.0.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (27, 'guanguans.dcat-login-captcha', 1, '1.0.9', 'Optimize booting `BootingHandler`.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (28, 'guanguans.dcat-login-captcha', 1, '1.0.9', 'Optimize setting form .', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (29, 'guanguans.dcat-login-captcha', 1, '1.0.10', 'Compatible callback type.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (30, 'guanguans.dcat-login-captcha', 1, '1.0.11', 'Rename `phrase_session_key` -> `captcha_phrase_session_key`.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (31, 'guanguans.dcat-login-captcha', 1, '1.0.11', 'Generate captcha random url.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (32, 'guanguans.dcat-login-captcha', 1, '1.0.11', 'Replace `Closure routing` -> `CaptchaController`.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (33, 'guanguans.dcat-login-captcha', 1, '1.0.11', 'Bump actions/cache from 2 to 3.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (34, 'guanguans.dcat-login-captcha', 1, '1.0.11', 'Bump actions/checkout from 2 to 3.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (35, 'guanguans.dcat-login-captcha', 1, '1.0.11', 'Update overtrue/phplint requirement from ^2.3 || ^3.0 to ^2.3 || ^3.0 || ^4.0.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (36, 'guanguans.dcat-login-captcha', 1, '1.0.12', 'Bump codecov/codecov-action from 2.1.0 to 3.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (37, 'guanguans.dcat-login-captcha', 1, '1.0.12', 'Update author info.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (38, 'guanguans.dcat-login-captcha', 1, '1.0.13', 'Update JS.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (39, 'guanguans.dcat-login-captcha', 1, '1.0.14', 'Rename login_captcha_get -> login_captcha_content.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (40, 'guanguans.dcat-login-captcha', 1, '1.0.14', 'Update github config files.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (41, 'guanguans.dcat-login-captcha', 1, '1.0.15', 'Fix captcha check.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (42, 'guanguans.dcat-login-captcha', 1, '1.0.16', 'Add migration files.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (43, 'guanguans.dcat-login-captcha', 1, '1.0.17', 'Fix migration file name.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (44, 'guanguans.dcat-login-captcha', 1, '1.0.18', 'Update to single action controller.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (45, 'guanguans.dcat-login-captcha', 1, '1.0.18', 'Fix setting.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (46, 'guanguans.dcat-login-captcha', 1, '1.0.18', 'Optimize migration file.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (47, 'guanguans.dcat-login-captcha', 1, '1.0.19', 'Fix loading config.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (48, 'guanguans.dcat-login-captcha', 1, '1.0.19', 'Remove version update migration.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (49, 'guanguans.dcat-login-captcha', 1, '1.0.19', 'Cancel service late registration.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (50, 'guanguans.dcat-login-captcha', 1, '1.1.0', 'chore(deps): update overtrue/phplint to support more versions.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (51, 'guanguans.dcat-login-captcha', 1, '1.1.0', 'update LoginCaptchaServiceProvider.php to merge config correctly(#27).', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (52, 'guanguans.dcat-login-captcha', 1, '1.1.0', 'Bump dependabot/fetch-metadata from 1.3.5 to 1.3.6.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (53, 'guanguans.dcat-login-captcha', 1, '1.1.0', 'Bump actions/stale from 6 to 7.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (54, 'guanguans.dcat-login-captcha', 1, '1.1.0', 'Update vimeo/psalm requirement from ^4.0 to ^4.0 || ^5.0.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (55, 'guanguans.dcat-login-captcha', 1, '1.1.0', 'Bump dependabot/fetch-metadata from 1.3.4 to 1.3.5.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (56, 'guanguans.dcat-login-captcha', 1, '1.1.0', 'Bump dependabot/fetch-metadata from 1.3.3 to 1.3.4.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (57, 'guanguans.dcat-login-captcha', 1, '1.1.0', 'Bump actions/stale from 5 to 6.', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (58, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'BootingHandler.php: add missing comment', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (59, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'readme: Update requirements and installation steps', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (60, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'vendor-bin: Add facade-documenter to composer.json', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (61, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'CaptchaBuilder: add CaptchaBuilder class', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (62, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'monorepo-builder-worker: Add guanguans/monorepo-builder-worker package', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (63, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'rector: Add RemoveInterfacesRector', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (64, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'rector: Add Rector configuration', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (65, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'admin: Handle Dcat login captcha upgrade error', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (66, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'controller: add return type declaration', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (67, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'tests.yml: Update PHP versions', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (68, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'xml: Fix InvalidReturnType and InvalidReturnStatement', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (69, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'captcha: Remove captcha.blade.php', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (70, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'lang: rename login_captcha.php to login-captcha.php', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (71, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'tests: Refactor TestCase', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (72, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'Merge pull request [#36](https://github.com/guanguans/monorepo-builder-worker/issues/36) from guanguans/dependabot/github_actions/stefanzweifel/git-auto-commit-action-5', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (73, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'Merge pull request [#34](https://github.com/guanguans/monorepo-builder-worker/issues/34) from guanguans/dependabot/github_actions/codecov/codecov-action-4', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (74, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'Merge pull request [#33](https://github.com/guanguans/monorepo-builder-worker/issues/33) from guanguans/dependabot/github_actions/actions/checkout-4', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (75, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'Merge pull request [#32](https://github.com/guanguans/monorepo-builder-worker/issues/32) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.6.0', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (76, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'Merge pull request [#31](https://github.com/guanguans/monorepo-builder-worker/issues/31) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.5.1', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (77, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'Merge pull request [#30](https://github.com/guanguans/monorepo-builder-worker/issues/30) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.5.0', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (78, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'Merge pull request [#29](https://github.com/guanguans/monorepo-builder-worker/issues/29) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.4.0', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (79, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC1', 'Merge pull request [#28](https://github.com/guanguans/monorepo-builder-worker/issues/28) from guanguans/dependabot/github_actions/actions/stale-8', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (80, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC2', 'src: Add initConfig method', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (81, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC2', 'tests: Install and enable login captcha extension', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (82, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC2', 'tests: Update test database', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (83, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC2', 'setting: remove unused handle method', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (84, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC2', 'tests: Refactor defineDatabaseMigrations method', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (85, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC2', 'FeatureTest: can generate login captcha', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (86, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC2', 'Support: can check login captcha', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (87, 'guanguans.dcat-login-captcha', 1, '2.0.0-RC2', 'TestCase: Fix database migrations', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (88, 'guanguans.dcat-login-captcha', 1, '2.0.0', 'readme: Update package links', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (89, 'guanguans.dcat-login-captcha', 1, '2.0.0', 'src: Update login captcha documentation', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (90, 'guanguans.dcat-login-captcha', 1, '2.0.0', 'views: Add captcha.blade.php view file', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (91, 'guanguans.dcat-login-captcha', 1, '2.0.0', 'serviceprovider: Fix LoginCaptchaServiceProvider validation', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (92, 'guanguans.dcat-login-captcha', 1, '2.0.0', 'src: Update LoginCaptchaServiceProvider.php', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (93, 'guanguans.dcat-login-captcha', 1, '2.0.0', 'LoginCaptchaServiceProvider: improve setupConfig method', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (94, 'guanguans.dcat-login-captcha', 1, '2.0.0', 'src: optimize LoginCaptchaServiceProvider', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (95, 'guanguans.dcat-login-captcha', 1, '2.0.1', 'middleware: Update SetResponseContentType middleware', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (96, 'guanguans.dcat-login-captcha', 1, '2.0.1', 'captcha: refactor captcha blade template', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (97, 'guanguans.dcat-login-captcha', 1, '2.0.1', 'FeatureTest: update login captcha test', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (98, 'guanguans.dcat-login-captcha', 1, '2.1.0', 'config: Support for multi apps control enabled login captcha', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (99, 'guanguans.dcat-login-captcha', 1, '2.1.0', 'readme: Update Composer installation instructions', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (100, 'guanguans.dcat-login-captcha', 1, '2.1.1', 'config: Remove unused path in StaticClosureRector class', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (101, 'guanguans.dcat-login-captcha', 1, '2.1.1', 'LoginCaptchaServiceProvider: use self instead of static', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (102, 'guanguans.dcat-login-captcha', 1, '2.1.1', 'setting: Add `enabled` switch to form', '2025-08-12 22:14:37', '2025-08-12 22:14:37');
INSERT INTO `admin_extension_histories` VALUES (103, 'guanguans.dcat-login-captcha', 1, '2.1.1', 'setting: Use this->trans() function to translate messages', '2025-08-12 22:14:37', '2025-08-12 22:14:37');

-- ----------------------------
-- Table structure for admin_extensions
-- ----------------------------
DROP TABLE IF EXISTS `admin_extensions`;
CREATE TABLE `admin_extensions`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_enabled` tinyint(4) NOT NULL DEFAULT 0,
  `options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `admin_extensions_name_unique`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_extensions
-- ----------------------------
INSERT INTO `admin_extensions` VALUES (1, 'dcat-admin.operation-log', '1.0.0', 1, NULL, '2025-08-12 22:06:26', '2025-08-12 22:06:31');
INSERT INTO `admin_extensions` VALUES (2, 'guanguans.dcat-login-captcha', '2.1.1', 0, NULL, '2025-08-12 22:14:37', '2025-08-12 22:15:04');

-- ----------------------------
-- Table structure for admin_login_logs
-- ----------------------------
DROP TABLE IF EXISTS `admin_login_logs`;
CREATE TABLE `admin_login_logs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员id',
  `admin_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '登录账号',
  `login_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '登录IP',
  `status` tinyint(3) UNSIGNED NOT NULL COMMENT '状态 1=成功 2=失败',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `login_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '今日登录次数',
  `fail_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '今日失败次数',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `admin_id`(`admin_id`) USING BTREE,
  INDEX `admin_name`(`admin_name`) USING BTREE,
  INDEX `login_ip`(`login_ip`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '管理员登录日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_login_logs
-- ----------------------------

-- ----------------------------
-- Table structure for admin_menu
-- ----------------------------
DROP TABLE IF EXISTS `admin_menu`;
CREATE TABLE `admin_menu`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `uri` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `extension` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `show` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 214 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_menu
-- ----------------------------
INSERT INTO `admin_menu` VALUES (1, 0, 1, '主页', 'feather icon-bar-chart-2', '/', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (2, 0, 59, '系统', 'feather icon-settings', '', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (3, 2, 60, '管理员', '', 'auth/users', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (4, 2, 61, '角色', '', 'auth/roles', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (5, 2, 62, '权限', '', 'auth/permissions', '', 0, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (6, 2, 63, '菜单', '', 'auth/menu', '', 0, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (7, 2, 64, '登录日志', '', 'auth/loginLogs', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (9, 2, 65, '操作日志', '', 'auth/operation-logs', 'dcat-admin.operation-log', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (10, 0, 2, '用户', 'fa-users', NULL, '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (11, 10, 3, '用户列表', 'fa-users', 'users', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (12, 10, 4, '用户数据', 'fa-list', 'userDatas', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (20, 0, 5, '资产', 'fa-money', NULL, '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (21, 20, 6, '用户资产', 'fa-list-ul', 'userAssets', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (22, 20, 7, '资产日志', 'fa-th-list', 'assetLogs', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (23, 20, 8, '系统充值扣款', 'fa-list', 'recharges', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (25, 20, 10, '支付记录', 'fa-list', 'payOrders', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (26, 20, 11, '提现记录', 'fa-list', 'withdraws', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (40, 0, 14, '团队', 'fa-users', NULL, '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (41, 40, 15, '等级设置', 'fa-list', 'levels', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (42, 40, 16, '等级记录', 'fa-list', 'levelHistories', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (43, 40, 17, '分享奖励', 'fa-list', 'inviteRewards', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (45, 40, 18, '团队奖励', 'fa-list', 'teamRewards', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (46, 40, 19, '奖励比例', 'fa-list', 'rewardRates', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (47, 40, 20, '推荐关系', 'fa-paw', 'teams', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (48, 40, 21, '推荐关系查询', 'fa-list', 'team/trees', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (49, 40, 22, '用户业绩', 'fa-list', 'userPerfs', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (50, 40, 23, '业绩日志', 'fa-list', 'userPerfLogs', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (60, 0, 27, '节点', 'fa-list', '', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (61, 60, 28, '节点产品', 'feather icon-settings', 'nodeProducts', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (62, 60, 29, '节点订单', 'fa-shopping-cart', 'nodeOrders', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (80, 0, 31, '算力', 'fa-list', NULL, '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (81, 80, 32, '资产包产品', 'fa-list', 'alphaProducts', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (82, 80, 33, '资产包订单', 'fa-shopping-cart', 'alphaOrders', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (83, 80, 34, '资产包收益', 'fa-list', 'alphaRewards', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (160, 0, 36, '分红', 'fa-list', NULL, '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (163, 160, 39, '手续费', 'fa-list', 'fees', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (164, 160, 40, '手续费明细', 'fa-list', 'feeLogs', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (165, 160, 41, '节点池分红', 'fa-list', 'nodeFeeAllocs', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (166, 160, 42, '节点池分红明细', 'fa-list', 'nodeFeeLogs', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (200, 0, 54, '设置', 'fa-anchor', NULL, '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (201, 200, 55, '系统配置', 'fa-toggle-on', 'config', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (202, 200, 56, '系统信息', 'fa-toggle-on', 'sysinfo', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (203, 200, 57, '区块链配置', 'fa-chain', 'chains', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (204, 200, 58, '代币管理', 'feather icon-settings', 'assets', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (210, 0, 47, '内容', 'fa-list', NULL, '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (211, 210, 48, '公告管理', 'fa-list', 'articles', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (212, 210, 49, '公告类型', 'fa-list', 'articleCates', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_menu` VALUES (213, 210, 50, 'Banner管理', 'fa-list', 'banners', '', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');

-- ----------------------------
-- Table structure for admin_operation_log
-- ----------------------------
DROP TABLE IF EXISTS `admin_operation_log`;
CREATE TABLE `admin_operation_log`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `input` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `admin_operation_log_user_id_index`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_operation_log
-- ----------------------------

-- ----------------------------
-- Table structure for admin_permission_menu
-- ----------------------------
DROP TABLE IF EXISTS `admin_permission_menu`;
CREATE TABLE `admin_permission_menu`  (
  `permission_id` bigint(20) NOT NULL,
  `menu_id` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE INDEX `admin_permission_menu_permission_id_menu_id_unique`(`permission_id`, `menu_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_permission_menu
-- ----------------------------
INSERT INTO `admin_permission_menu` VALUES (1, 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permission_menu` VALUES (2, 2, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permission_menu` VALUES (3, 3, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permission_menu` VALUES (4, 4, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permission_menu` VALUES (5, 5, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permission_menu` VALUES (6, 6, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permission_menu` VALUES (7, 7, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permission_menu` VALUES (9, 9, '2000-01-01 00:00:00', '2000-01-01 00:00:00');

-- ----------------------------
-- Table structure for admin_permissions
-- ----------------------------
DROP TABLE IF EXISTS `admin_permissions`;
CREATE TABLE `admin_permissions`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `http_method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `http_path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `parent_id` bigint(20) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `admin_permissions_slug_unique`(`slug`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_permissions
-- ----------------------------
INSERT INTO `admin_permissions` VALUES (1, '主页', '主页', NULL, '/', 1, 0, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permissions` VALUES (2, '系统', '系统', '', '', 47, 0, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permissions` VALUES (3, '管理员', '管理员', '', '/auth/users*', 48, 2, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permissions` VALUES (4, '角色', '角色', '', '/auth/roles*', 49, 2, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permissions` VALUES (5, '权限', '权限', '', '/auth/permissions*', 50, 2, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permissions` VALUES (6, '菜单', '菜单', '', '/auth/menu*', 51, 2, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permissions` VALUES (7, '登录日志', '登录日志', '', 'auth/loginLogs*', 52, 2, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `admin_permissions` VALUES (9, '操作日志', '操作日志', '', 'auth/operation-logs*', 53, 2, '2000-01-01 00:00:00', '2000-01-01 00:00:00');

-- ----------------------------
-- Table structure for admin_role_menu
-- ----------------------------
DROP TABLE IF EXISTS `admin_role_menu`;
CREATE TABLE `admin_role_menu`  (
  `role_id` bigint(20) NOT NULL,
  `menu_id` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE INDEX `admin_role_menu_role_id_menu_id_unique`(`role_id`, `menu_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_role_menu
-- ----------------------------

-- ----------------------------
-- Table structure for admin_role_permissions
-- ----------------------------
DROP TABLE IF EXISTS `admin_role_permissions`;
CREATE TABLE `admin_role_permissions`  (
  `role_id` bigint(20) NOT NULL,
  `permission_id` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE INDEX `admin_role_permissions_role_id_permission_id_unique`(`role_id`, `permission_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_role_permissions
-- ----------------------------

-- ----------------------------
-- Table structure for admin_role_users
-- ----------------------------
DROP TABLE IF EXISTS `admin_role_users`;
CREATE TABLE `admin_role_users`  (
  `role_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE INDEX `admin_role_users_role_id_user_id_unique`(`role_id`, `user_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_role_users
-- ----------------------------
INSERT INTO `admin_role_users` VALUES (1, 1, '2025-08-12 13:25:59', '2025-08-12 13:25:59');
INSERT INTO `admin_role_users` VALUES (1, 2, '2026-01-15 11:38:57', '2026-01-15 11:38:57');

-- ----------------------------
-- Table structure for admin_roles
-- ----------------------------
DROP TABLE IF EXISTS `admin_roles`;
CREATE TABLE `admin_roles`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `admin_roles_slug_unique`(`slug`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_roles
-- ----------------------------
INSERT INTO `admin_roles` VALUES (1, 'Administrator', 'administrator', '2025-08-12 13:25:59', '2025-08-12 13:25:59');

-- ----------------------------
-- Table structure for admin_settings
-- ----------------------------
DROP TABLE IF EXISTS `admin_settings`;
CREATE TABLE `admin_settings`  (
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`slug`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_settings
-- ----------------------------
INSERT INTO `admin_settings` VALUES ('site_lang', 'zh_CN', '2024-06-15 09:45:43', '2024-06-15 09:45:43');

-- ----------------------------
-- Table structure for admin_users
-- ----------------------------
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `last_session` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `google2fa_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `google2fa_ts` int(11) NULL DEFAULT 0,
  `password_changed_at` datetime NULL DEFAULT NULL,
  `last_login_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `last_login_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `admin_users_username_unique`(`username`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_users
-- ----------------------------
INSERT INTO `admin_users` VALUES (1, 'admin', '$2y$12$ZcijIZrd7radG7jwgPvAf.P5L0RMKF3o.i7xExntCizgRz/XT5e9.', 'Administrator', NULL, 'EPgGq35jnkS90NRsdZOtzzSXVr0JxbDJdbuS1dbjlVEltmtXhgE0r8KMUbhb', '2000-01-01 00:00:00', '2026-09-16 15:07:07', NULL, NULL, NULL, 0, NULL, '127.0.0.1', '2026-09-16 15:07:07');

-- ----------------------------
-- Table structure for alpha_orders
-- ----------------------------
DROP TABLE IF EXISTS `alpha_orders`;
CREATE TABLE `alpha_orders`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `product_id` int(10) UNSIGNED NOT NULL COMMENT '产品id',
  `sn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '单号',
  `amount` decimal(20, 6) UNSIGNED NOT NULL COMMENT '本金',
  `power_am` decimal(20, 6) UNSIGNED NOT NULL COMMENT '算力值',
  `token_price` decimal(20, 8) UNSIGNED NOT NULL DEFAULT 0.00000000 COMMENT '代币汇率',
  `reward_rate` decimal(10, 2) UNSIGNED NOT NULL COMMENT '月产率快照(%)',
  `quota_reward` decimal(20, 6) UNSIGNED NOT NULL COMMENT '静态收益封顶=2.5*本金',
  `rem_reward` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '剩余可产出收益',
  `rel_reward` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '累计已产出',
  `last_reward` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '最近产出',
  `last_day` date NULL DEFAULT NULL COMMENT '最近产出日期',
  `trial_end` datetime NULL DEFAULT NULL COMMENT '首月试用截止',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0=待支付 1=进行中 2=已完成 3=已退出 4=取消',
  `pay_at` datetime NULL DEFAULT NULL COMMENT '支付时间',
  `done_at` datetime NULL DEFAULT NULL COMMENT '完成时间',
  `exit_at` datetime NULL DEFAULT NULL COMMENT '退出时间',
  `fail_at` datetime NULL DEFAULT NULL COMMENT '取消/失败时间',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `alpha_orders_sn_unique`(`sn`) USING BTREE,
  INDEX `alpha_orders_user_id_index`(`user_id`) USING BTREE,
  INDEX `alpha_orders_status_index`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of alpha_orders
-- ----------------------------

-- ----------------------------
-- Table structure for alpha_products
-- ----------------------------
DROP TABLE IF EXISTS `alpha_products`;
CREATE TABLE `alpha_products`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '产品名称',
  `tier` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '档位 basic/high',
  `min_amount` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '最小申购额',
  `max_amount` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '最大申购额',
  `per_power` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '每单位算力值',
  `min_rate` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '最低月产率(%)',
  `max_rate` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '最高月产率(%)',
  `base_rate` decimal(10, 2) UNSIGNED NOT NULL COMMENT '基础月产率(%)',
  `node_rate` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '持节点加速月产率(%)',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态 1=启用 0=停用',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of alpha_products
-- ----------------------------
INSERT INTO `alpha_products` VALUES (1, '基础算力方案', 'basic', 100.000000, 5000.000000, 1.000000, 5.00, 17.00, 17.00, 20.00, 1, 0, '2026-09-02 11:46:39', '2026-09-08 16:00:08');
INSERT INTO `alpha_products` VALUES (2, '高额算力方案', 'high', 5000.000000, 100000.000000, 1.200000, 7.00, 21.00, 21.00, 30.00, 1, 0, '2026-09-02 11:47:12', '2026-09-02 11:47:12');

-- ----------------------------
-- Table structure for alpha_rewards
-- ----------------------------
DROP TABLE IF EXISTS `alpha_rewards`;
CREATE TABLE `alpha_rewards`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `order_id` int(10) UNSIGNED NOT NULL COMMENT '订单id',
  `order_am` decimal(20, 6) UNSIGNED NOT NULL COMMENT '订单金额',
  `rate` decimal(10, 2) UNSIGNED NOT NULL COMMENT '月产率(%)',
  `reward_am` decimal(20, 6) UNSIGNED NOT NULL COMMENT '本次产出额',
  `quota_used_am` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '计入额度槽金额',
  `frozen_am` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '本次冻结金额',
  `settle_date` date NOT NULL COMMENT '结算日',
  `created_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `alpha_rewards_order_id_settle_date_unique`(`order_id`, `settle_date`) USING BTREE,
  INDEX `alpha_rewards_user_id_index`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of alpha_rewards
-- ----------------------------

-- ----------------------------
-- Table structure for article_attrs
-- ----------------------------
DROP TABLE IF EXISTS `article_attrs`;
CREATE TABLE `article_attrs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` int(10) UNSIGNED NOT NULL COMMENT '节点id',
  `lang` int(10) UNSIGNED NOT NULL COMMENT '语言',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名称',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '描述',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `article_id`(`article_id`, `lang`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '文章属性表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of article_attrs
-- ----------------------------
INSERT INTO `article_attrs` VALUES (1, 1, 1, '这是公告标题', '这是公告内容', '2026-04-03 18:54:37', '2026-04-03 18:54:37', NULL);
INSERT INTO `article_attrs` VALUES (2, 1, 2, '这是公告标题', '这是公告内容', '2026-04-03 18:54:37', '2026-04-03 18:54:37', NULL);
INSERT INTO `article_attrs` VALUES (3, 1, 3, '这是公告标题', '这是公告内容', '2026-04-03 18:54:37', '2026-04-03 18:54:37', NULL);
INSERT INTO `article_attrs` VALUES (4, 1, 4, '这是公告标题', '这是公告内容', '2026-04-03 18:54:37', '2026-04-03 18:54:37', NULL);
INSERT INTO `article_attrs` VALUES (5, 1, 5, '这是公告标题', '这是公告内容', '2026-04-03 18:54:37', '2026-04-03 18:54:37', NULL);
INSERT INTO `article_attrs` VALUES (6, 1, 6, '这是公告标题', '这是公告内容', '2026-04-03 18:54:37', '2026-04-03 18:54:37', NULL);

-- ----------------------------
-- Table structure for article_cate_attrs
-- ----------------------------
DROP TABLE IF EXISTS `article_cate_attrs`;
CREATE TABLE `article_cate_attrs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cate_id` int(10) UNSIGNED NOT NULL COMMENT '类型id',
  `lang` int(10) UNSIGNED NOT NULL COMMENT '语言',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名称',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `cate_id`(`cate_id`, `lang`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '文章类型属性表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of article_cate_attrs
-- ----------------------------
INSERT INTO `article_cate_attrs` VALUES (1, 1, 1, 'Platform Announcement', '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `article_cate_attrs` VALUES (2, 1, 2, '平台公告', '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `article_cate_attrs` VALUES (3, 1, 3, '平台公告', '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `article_cate_attrs` VALUES (4, 1, 4, 'プラットフォームのお知らせ', '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `article_cate_attrs` VALUES (5, 1, 5, '플랫폼 공지사항', '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `article_cate_attrs` VALUES (6, 2, 1, 'System Update', '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `article_cate_attrs` VALUES (7, 2, 2, '系统更新', '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `article_cate_attrs` VALUES (8, 2, 3, '系统更新', '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `article_cate_attrs` VALUES (9, 2, 4, 'システム更新', '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `article_cate_attrs` VALUES (10, 2, 5, '시스템 업데이트', '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);

-- ----------------------------
-- Table structure for article_cates
-- ----------------------------
DROP TABLE IF EXISTS `article_cates`;
CREATE TABLE `article_cates`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名称',
  `color` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '颜色',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '文章类型' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of article_cates
-- ----------------------------
INSERT INTO `article_cates` VALUES (1, '平台公告', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `article_cates` VALUES (2, '系统更新', 2, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);

-- ----------------------------
-- Table structure for articles
-- ----------------------------
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cate_id` int(10) UNSIGNED NOT NULL COMMENT '类型id',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标题',
  `mini_img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '列表图片',
  `images` json NULL COMMENT '正文图片',
  `is_show` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否显示',
  `is_top` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否置顶',
  `order` int(11) NOT NULL DEFAULT 0 COMMENT '排序权重',
  `publish_time` datetime NULL DEFAULT NULL COMMENT '发布时间',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `publish_time`(`publish_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '文章' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of articles
-- ----------------------------
INSERT INTO `articles` VALUES (1, 1, '这是公告标题', NULL, NULL, 1, 0, 1, '2026-04-03 18:52:45', '2026-04-03 18:54:37', '2026-04-22 17:09:42', NULL);

-- ----------------------------
-- Table structure for asset_logs
-- ----------------------------
DROP TABLE IF EXISTS `asset_logs`;
CREATE TABLE `asset_logs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `change_type` tinyint(3) UNSIGNED NOT NULL COMMENT '变更类型 1=入 2=冻结 3=出 4=回撤',
  `type` tinyint(3) UNSIGNED NOT NULL COMMENT '变动类型',
  `symbol` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '币种名称',
  `amount` decimal(20, 6) NOT NULL DEFAULT 0.000000 COMMENT '变动金额',
  `before_amount` decimal(20, 6) NOT NULL DEFAULT 0.000000 COMMENT '变动前',
  `after_amount` decimal(20, 6) NOT NULL DEFAULT 0.000000 COMMENT '变动后',
  `before_freeze` decimal(20, 6) NOT NULL DEFAULT 0.000000 COMMENT '变动前(冻结金额)',
  `after_freeze` decimal(20, 6) NOT NULL DEFAULT 0.000000 COMMENT '变动后(冻结金额)',
  `created_at` datetime NULL DEFAULT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `asset_logs_user_id_index`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of asset_logs
-- ----------------------------

-- ----------------------------
-- Table structure for assets
-- ----------------------------
DROP TABLE IF EXISTS `assets`;
CREATE TABLE `assets`  (
  `id` bigint(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `chain_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '区块链id',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '币种单位',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '币种标题',
  `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '币种CODE',
  `decimals` tinyint(4) NOT NULL DEFAULT 18 COMMENT '精度',
  `type` tinyint(3) NOT NULL DEFAULT 1 COMMENT '类型 1=主币 2=代币',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 0=禁用  1=启用',
  `is_exchange` tinyint(1) NULL DEFAULT 1 COMMENT '是否开启兑换',
  `exchange_rate` decimal(19, 8) NULL DEFAULT 0.00000000 COMMENT '汇率',
  `contract_abi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '币种的token_abi',
  `contract_address` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '代币合约地址',
  `withdraw_address` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '提现钱包地址',
  `withdraw_key` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '提现钱包加密私钥',
  `wallet_address` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '收款钱包地址',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `chain_id`(`chain_id`) USING BTREE,
  INDEX `code`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '系统币种表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of assets
-- ----------------------------
INSERT INTO `assets` VALUES (1, 56, 'USDT', 'USDT正式币', 'USDT', 18, 2, 1, 1, 1.00000000, '[{\"inputs\":[{\"internalType\":\"uint256\",\"name\":\"initialSupply\",\"type\":\"uint256\"}],\"stateMutability\":\"nonpayable\",\"type\":\"constructor\"},{\"anonymous\":false,\"inputs\":[{\"indexed\":true,\"internalType\":\"address\",\"name\":\"owner\",\"type\":\"address\"},{\"indexed\":true,\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"},{\"indexed\":false,\"internalType\":\"uint256\",\"name\":\"value\",\"type\":\"uint256\"}],\"name\":\"Approval\",\"type\":\"event\"},{\"anonymous\":false,\"inputs\":[{\"indexed\":true,\"internalType\":\"address\",\"name\":\"from\",\"type\":\"address\"},{\"indexed\":true,\"internalType\":\"address\",\"name\":\"to\",\"type\":\"address\"},{\"indexed\":false,\"internalType\":\"uint256\",\"name\":\"value\",\"type\":\"uint256\"}],\"name\":\"Transfer\",\"type\":\"event\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"owner\",\"type\":\"address\"},{\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"}],\"name\":\"allowance\",\"outputs\":[{\"internalType\":\"uint256\",\"name\":\"\",\"type\":\"uint256\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"amount\",\"type\":\"uint256\"}],\"name\":\"approve\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"account\",\"type\":\"address\"}],\"name\":\"balanceOf\",\"outputs\":[{\"internalType\":\"uint256\",\"name\":\"\",\"type\":\"uint256\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"decimals\",\"outputs\":[{\"internalType\":\"uint8\",\"name\":\"\",\"type\":\"uint8\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"subtractedValue\",\"type\":\"uint256\"}],\"name\":\"decreaseAllowance\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"addedValue\",\"type\":\"uint256\"}],\"name\":\"increaseAllowance\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"name\",\"outputs\":[{\"internalType\":\"string\",\"name\":\"\",\"type\":\"string\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"symbol\",\"outputs\":[{\"internalType\":\"string\",\"name\":\"\",\"type\":\"string\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"totalSupply\",\"outputs\":[{\"internalType\":\"uint256\",\"name\":\"\",\"type\":\"uint256\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"to\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"amount\",\"type\":\"uint256\"}],\"name\":\"transfer\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"from\",\"type\":\"address\"},{\"internalType\":\"address\",\"name\":\"to\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"amount\",\"type\":\"uint256\"}],\"name\":\"transferFrom\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"}]', '0x55d398326f99059fF775485246999027B3197955', '', '', '', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `assets` VALUES (2, 97, 'tUSDT', 'USDT测试币', 'USDT', 18, 2, 1, 1, 1.00000000, '[{\"inputs\":[{\"internalType\":\"uint256\",\"name\":\"initialSupply\",\"type\":\"uint256\"},{\"internalType\":\"address\",\"name\":\"receiver\",\"type\":\"address\"}],\"stateMutability\":\"nonpayable\",\"type\":\"constructor\"},{\"anonymous\":false,\"inputs\":[{\"indexed\":true,\"internalType\":\"address\",\"name\":\"owner\",\"type\":\"address\"},{\"indexed\":true,\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"},{\"indexed\":false,\"internalType\":\"uint256\",\"name\":\"value\",\"type\":\"uint256\"}],\"name\":\"Approval\",\"type\":\"event\"},{\"anonymous\":false,\"inputs\":[{\"indexed\":true,\"internalType\":\"address\",\"name\":\"from\",\"type\":\"address\"},{\"indexed\":true,\"internalType\":\"address\",\"name\":\"to\",\"type\":\"address\"},{\"indexed\":false,\"internalType\":\"uint256\",\"name\":\"value\",\"type\":\"uint256\"}],\"name\":\"Transfer\",\"type\":\"event\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"owner\",\"type\":\"address\"},{\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"}],\"name\":\"allowance\",\"outputs\":[{\"internalType\":\"uint256\",\"name\":\"\",\"type\":\"uint256\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"amount\",\"type\":\"uint256\"}],\"name\":\"approve\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"account\",\"type\":\"address\"}],\"name\":\"balanceOf\",\"outputs\":[{\"internalType\":\"uint256\",\"name\":\"\",\"type\":\"uint256\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"decimals\",\"outputs\":[{\"internalType\":\"uint8\",\"name\":\"\",\"type\":\"uint8\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"_to\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"_value\",\"type\":\"uint256\"}],\"name\":\"mint\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"success\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"name\",\"outputs\":[{\"internalType\":\"string\",\"name\":\"\",\"type\":\"string\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"symbol\",\"outputs\":[{\"internalType\":\"string\",\"name\":\"\",\"type\":\"string\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"totalSupply\",\"outputs\":[{\"internalType\":\"uint256\",\"name\":\"\",\"type\":\"uint256\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"recipient\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"amount\",\"type\":\"uint256\"}],\"name\":\"transfer\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"sender\",\"type\":\"address\"},{\"internalType\":\"address\",\"name\":\"recipient\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"amount\",\"type\":\"uint256\"}],\"name\":\"transferFrom\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"}]', '0x821F1b5d8f12550822Cc6132294599C6fC92b3d5', '', '', '', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `assets` VALUES (3, 56, 'NOXO', 'NOXO正式币', 'NOX', 18, 2, 1, 1, 0.10000000, '[{\"inputs\":[{\"internalType\":\"string\",\"name\":\"name_\",\"type\":\"string\"},{\"internalType\":\"string\",\"name\":\"symbol_\",\"type\":\"string\"},{\"internalType\":\"uint256\",\"name\":\"supply_\",\"type\":\"uint256\"},{\"internalType\":\"address\",\"name\":\"to_\",\"type\":\"address\"}],\"stateMutability\":\"nonpayable\",\"type\":\"constructor\"},{\"anonymous\":false,\"inputs\":[{\"indexed\":true,\"internalType\":\"address\",\"name\":\"owner\",\"type\":\"address\"},{\"indexed\":true,\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"},{\"indexed\":false,\"internalType\":\"uint256\",\"name\":\"value\",\"type\":\"uint256\"}],\"name\":\"Approval\",\"type\":\"event\"},{\"anonymous\":false,\"inputs\":[{\"indexed\":true,\"internalType\":\"address\",\"name\":\"from\",\"type\":\"address\"},{\"indexed\":true,\"internalType\":\"address\",\"name\":\"to\",\"type\":\"address\"},{\"indexed\":false,\"internalType\":\"uint256\",\"name\":\"value\",\"type\":\"uint256\"}],\"name\":\"Transfer\",\"type\":\"event\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"owner\",\"type\":\"address\"},{\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"}],\"name\":\"allowance\",\"outputs\":[{\"internalType\":\"uint256\",\"name\":\"\",\"type\":\"uint256\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"amount\",\"type\":\"uint256\"}],\"name\":\"approve\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"account\",\"type\":\"address\"}],\"name\":\"balanceOf\",\"outputs\":[{\"internalType\":\"uint256\",\"name\":\"\",\"type\":\"uint256\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"uint256\",\"name\":\"amount\",\"type\":\"uint256\"}],\"name\":\"burn\",\"outputs\":[],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"decimals\",\"outputs\":[{\"internalType\":\"uint8\",\"name\":\"\",\"type\":\"uint8\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"subtractedValue\",\"type\":\"uint256\"}],\"name\":\"decreaseAllowance\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"spender\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"addedValue\",\"type\":\"uint256\"}],\"name\":\"increaseAllowance\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"name\",\"outputs\":[{\"internalType\":\"string\",\"name\":\"\",\"type\":\"string\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"symbol\",\"outputs\":[{\"internalType\":\"string\",\"name\":\"\",\"type\":\"string\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[],\"name\":\"totalSupply\",\"outputs\":[{\"internalType\":\"uint256\",\"name\":\"\",\"type\":\"uint256\"}],\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"to\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"amount\",\"type\":\"uint256\"}],\"name\":\"transfer\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"inputs\":[{\"internalType\":\"address\",\"name\":\"from\",\"type\":\"address\"},{\"internalType\":\"address\",\"name\":\"to\",\"type\":\"address\"},{\"internalType\":\"uint256\",\"name\":\"amount\",\"type\":\"uint256\"}],\"name\":\"transferFrom\",\"outputs\":[{\"internalType\":\"bool\",\"name\":\"\",\"type\":\"bool\"}],\"stateMutability\":\"nonpayable\",\"type\":\"function\"}]', '0x5191b13fc0E90026942dA71D619d64f4289Ddc69', NULL, NULL, NULL, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `assets` VALUES (4, 97, 'tNOXO', 'NOXO测试币', 'NOX', 18, 2, 1, 1, 0.10000000, '[{\"constant\":true,\"inputs\":[],\"name\":\"name\",\"outputs\":[{\"name\":\"\",\"type\":\"string\"}],\"payable\":false,\"stateMutability\":\"view\",\"type\":\"function\"},{\"constant\":false,\"inputs\":[{\"name\":\"_spender\",\"type\":\"address\"},{\"name\":\"_value\",\"type\":\"uint256\"}],\"name\":\"approve\",\"outputs\":[{\"name\":\"success\",\"type\":\"bool\"}],\"payable\":false,\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"constant\":true,\"inputs\":[],\"name\":\"totalSupply\",\"outputs\":[{\"name\":\"\",\"type\":\"uint256\"}],\"payable\":false,\"stateMutability\":\"view\",\"type\":\"function\"},{\"constant\":false,\"inputs\":[{\"name\":\"_from\",\"type\":\"address\"},{\"name\":\"_to\",\"type\":\"address\"},{\"name\":\"_value\",\"type\":\"uint256\"}],\"name\":\"transferFrom\",\"outputs\":[{\"name\":\"success\",\"type\":\"bool\"}],\"payable\":false,\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"constant\":true,\"inputs\":[],\"name\":\"decimals\",\"outputs\":[{\"name\":\"\",\"type\":\"uint8\"}],\"payable\":false,\"stateMutability\":\"view\",\"type\":\"function\"},{\"constant\":false,\"inputs\":[{\"name\":\"_to\",\"type\":\"address\"},{\"name\":\"_value\",\"type\":\"uint256\"}],\"name\":\"mint\",\"outputs\":[{\"name\":\"success\",\"type\":\"bool\"}],\"payable\":false,\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"constant\":true,\"inputs\":[{\"name\":\"_owner\",\"type\":\"address\"}],\"name\":\"balanceOf\",\"outputs\":[{\"name\":\"balance\",\"type\":\"uint256\"}],\"payable\":false,\"stateMutability\":\"view\",\"type\":\"function\"},{\"constant\":true,\"inputs\":[],\"name\":\"symbol\",\"outputs\":[{\"name\":\"\",\"type\":\"string\"}],\"payable\":false,\"stateMutability\":\"view\",\"type\":\"function\"},{\"constant\":false,\"inputs\":[{\"name\":\"_to\",\"type\":\"address\"},{\"name\":\"_value\",\"type\":\"uint256\"}],\"name\":\"transfer\",\"outputs\":[{\"name\":\"success\",\"type\":\"bool\"}],\"payable\":false,\"stateMutability\":\"nonpayable\",\"type\":\"function\"},{\"constant\":true,\"inputs\":[{\"name\":\"_owner\",\"type\":\"address\"},{\"name\":\"_spender\",\"type\":\"address\"}],\"name\":\"allowance\",\"outputs\":[{\"name\":\"remaining\",\"type\":\"uint256\"}],\"payable\":false,\"stateMutability\":\"view\",\"type\":\"function\"},{\"inputs\":[{\"name\":\"_initialAmount\",\"type\":\"uint256\"},{\"name\":\"_tokenName\",\"type\":\"string\"},{\"name\":\"_decimalUnits\",\"type\":\"uint8\"},{\"name\":\"_tokenSymbol\",\"type\":\"string\"}],\"payable\":false,\"stateMutability\":\"nonpayable\",\"type\":\"constructor\"},{\"anonymous\":false,\"inputs\":[{\"indexed\":true,\"name\":\"_from\",\"type\":\"address\"},{\"indexed\":true,\"name\":\"_to\",\"type\":\"address\"},{\"indexed\":false,\"name\":\"_value\",\"type\":\"uint256\"}],\"name\":\"Transfer\",\"type\":\"event\"},{\"anonymous\":false,\"inputs\":[{\"indexed\":true,\"name\":\"_owner\",\"type\":\"address\"},{\"indexed\":true,\"name\":\"_spender\",\"type\":\"address\"},{\"indexed\":false,\"name\":\"_value\",\"type\":\"uint256\"}],\"name\":\"Approval\",\"type\":\"event\"}]', '0x45ff98EE160c2DB1189a016345ae2f7265A36b88', NULL, NULL, NULL, '2000-01-01 00:00:00', '2000-01-01 00:00:00');

-- ----------------------------
-- Table structure for banners
-- ----------------------------
DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `pos_id` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '位置',
  `img` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '图片',
  `href` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '链接',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '备注',
  `is_show` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否显示',
  `order` int(11) NOT NULL DEFAULT 0 COMMENT '排序权重',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `is_show`(`is_show`, `order`) USING BTREE,
  INDEX `order`(`order`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'banner' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of banners
-- ----------------------------
INSERT INTO `banners` VALUES (1, 1, 'banners/index-banner1.png', NULL, '首页顶部广告图', 1, 0, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `banners` VALUES (2, 1, 'banners/index-banner2.png', NULL, '首页顶部广告图', 1, 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `banners` VALUES (3, 1, 'banners/index-banner3.png', NULL, '首页顶部广告图', 1, 2, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `banners` VALUES (4, 1, 'banners/index-banner4.png', NULL, '首页顶部广告图', 1, 3, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);

-- ----------------------------
-- Table structure for cache
-- ----------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache`  (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of cache
-- ----------------------------

-- ----------------------------
-- Table structure for cache_locks
-- ----------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks`  (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of cache_locks
-- ----------------------------

-- ----------------------------
-- Table structure for chains
-- ----------------------------
DROP TABLE IF EXISTS `chains`;
CREATE TABLE `chains`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '链名称',
  `alias` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '别名',
  `chain_id` int(10) NOT NULL DEFAULT 0 COMMENT '链id',
  `symbol` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '符号',
  `decimals` tinyint(11) NOT NULL DEFAULT 0 COMMENT '精数',
  `rpc_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'rpc地址',
  `scan_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '区块浏览器地址',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '是否开启',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  `deleted_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '区块链列表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of chains
-- ----------------------------
INSERT INTO `chains` VALUES (1, 'Binance Smart Chain', '币安链主链', 56, 'BNB', 18, 'https://bsc-dataseed1.defibit.io', 'https://bscscan.com', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `chains` VALUES (2, 'Binance Smart Chain Testnet', '币安测试链', 97, 'tBNB', 18, 'https://bsc-testnet-rpc.publicnode.com', 'https://testnet.bscscan.com', 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);

-- ----------------------------
-- Table structure for configs
-- ----------------------------
DROP TABLE IF EXISTS `configs`;
CREATE TABLE `configs`  (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置键',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '配置值',
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置组',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`key`) USING BTREE,
  UNIQUE INDEX `key`(`key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '参数设置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of configs
-- ----------------------------
INSERT INTO `configs` VALUES ('alpha_node_fee_rate', '90', 'node', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('alpha_quota_multiple', '2.5', 'power', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('not_allow_to_user_login', '0', 'base', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_allow_node_buy', '1', 'node', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_allow_power_buy', '1', 'power', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_allow_w_nox', '1', 'withdraw', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_allow_w_usdt', '1', 'withdraw', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_contact_tg', 'NOX998', 'contact', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_contact_x', 'NOX998', 'contact', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_fee_burn_rate', '30', 'fee', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_fee_dao_rate', '25', 'fee', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_fee_node1_rate', '5', 'fee', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_fee_node2_rate', '15', 'fee', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_fee_node3_rate', '25', 'fee', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_show_contact', '1', 'contact', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_w_fee_rate_nox', '5', 'withdraw', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_w_fee_rate_usdt', '5', 'withdraw', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_w_min_nox', '1', 'withdraw', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_w_min_usdt', '1', 'withdraw', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_w_nox_global_limit', '100000', 'withdraw', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_w_nox_user_limit', '10000', 'withdraw', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_w_usdt_global_limit', '100000', 'withdraw', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `configs` VALUES ('site_w_usdt_user_limit', '10000', 'withdraw', '2000-01-01 00:00:00', '2000-01-01 00:00:00');

-- ----------------------------
-- Table structure for content_attrs
-- ----------------------------
DROP TABLE IF EXISTS `content_attrs`;
CREATE TABLE `content_attrs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `content_id` int(10) UNSIGNED NOT NULL COMMENT '内容id',
  `lang` int(10) UNSIGNED NOT NULL COMMENT '语言',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '内容',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `content_id`(`content_id`, `lang`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '内容属性表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of content_attrs
-- ----------------------------
INSERT INTO `content_attrs` VALUES (1, 1, 1, NULL, '平台介绍', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (2, 2, 1, NULL, '[\"111111\",\"222222\",\"333333\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (3, 3, 1, NULL, '[\"111111\",\"222222\",\"333333\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (4, 4, 1, NULL, '[\"111111\",\"222222\",\"333333\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (9, 9, 1, NULL, '[\"随机抽取一名幸运用户获得后10位用户的5%的奖励\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (10, 2, 2, NULL, '[\"111111\",\"222222\",\"333333\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (11, 2, 3, NULL, '[\"111111\",\"222222\",\"333333\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (12, 2, 4, NULL, '[\"111111\",\"222222\",\"333333\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (13, 2, 5, NULL, '[\"111111\",\"222222\",\"333333\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (14, 2, 6, NULL, '[\"111111\",\"222222\",\"333333\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (15, 9, 2, NULL, '[\"随机抽取一名幸运用户获得后10位用户的5%的奖励\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (16, 9, 3, NULL, '[\"随机抽取一名幸运用户获得后10位用户的5%的奖励\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (17, 9, 4, NULL, '[\"随机抽取一名幸运用户获得后10位用户的5%的奖励\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (18, 9, 5, NULL, '[\"随机抽取一名幸运用户获得后10位用户的5%的奖励\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `content_attrs` VALUES (19, 9, 6, NULL, '[\"随机抽取一名幸运用户获得后10位用户的5%的奖励\"]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');

-- ----------------------------
-- Table structure for contents
-- ----------------------------
DROP TABLE IF EXISTS `contents`;
CREATE TABLE `contents`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'code',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标题',
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '数据',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `code`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '内容表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of contents
-- ----------------------------
INSERT INTO `contents` VALUES (1, 'PLAT_INTRO', NULL, 'defgault/contents/files/intro.mp4', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `contents` VALUES (2, 'SHORT_TERM_GOAL', NULL, 'default/contents/images/short.png', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `contents` VALUES (3, 'MID_TERM_GOAL', NULL, 'default/contents/images/mid.png', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `contents` VALUES (4, 'LONG_TERM_GOAL', NULL, 'default/contents/images/long.png', '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `contents` VALUES (9, 'LUCKY_RULES', NULL, NULL, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `contents` VALUES (10, 'LINES', NULL, '[{\"date\":\"2026 Q1\",\"content_1\":\"完善NFT挖矿\",\"content_2\":\"完善NFT挖矿\",\"content_3\":\"完善NFT挖矿\",\"content_4\":\"完善NFT挖矿\",\"content_5\":\"完善NFT挖矿\",\"content_6\":\"完善NFT挖矿\"},{\"date\":\"2026 Q2\",\"content_1\":\"建立核心社区\",\"content_2\":\"建立核心社区\",\"content_3\":\"建立核心社区\",\"content_4\":\"建立核心社区\",\"content_5\":\"建立核心社区\",\"content_6\":\"建立核心社区\"},{\"date\":\"2026 Q3\",\"content_1\":\"完成生态搭建\",\"content_2\":\"完成生态搭建\",\"content_3\":\"完成生态搭建\",\"content_4\":\"完成生态搭建\",\"content_5\":\"完成生态搭建\",\"content_6\":\"完成生态搭建\"},{\"date\":\"2026 Q3\",\"content_1\":\"RWA锚定NFT\",\"content_2\":\"RWA锚定NFT\",\"content_3\":\"RWA锚定NFT\",\"content_4\":\"RWA锚定NFT\",\"content_5\":\"RWA锚定NFT\",\"content_6\":\"RWA锚定NFT\"},{\"date\":\"2026 Q4\",\"content_1\":\"上线游戏板块\",\"content_2\":\"上线游戏板块\",\"content_3\":\"上线游戏板块\",\"content_4\":\"上线游戏板块\",\"content_5\":\"上线游戏板块\",\"content_6\":\"上线游戏板块\"},{\"date\":\"2027 Q1\",\"content_1\":\"跨平台生态互联\",\"content_2\":\"跨平台生态互联\",\"content_3\":\"跨平台生态互联\",\"content_4\":\"跨平台生态互联\",\"content_5\":\"跨平台生态互联\",\"content_6\":\"跨平台生态互联\"}]', '2000-01-01 00:00:00', '2000-01-01 00:00:00');

-- ----------------------------
-- Table structure for databases
-- ----------------------------
DROP TABLE IF EXISTS `databases`;
CREATE TABLE `databases`  (
  `type` tinyint(3) UNSIGNED NOT NULL COMMENT '类型',
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '数据',
  PRIMARY KEY (`type`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '数据库' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of databases
-- ----------------------------
INSERT INTO `databases` VALUES (1, '[{\"lang\":\"1\",\"title\":\"TPC\",\"content\":\"这是数据库页面文案，正式上线请替换为正式文本\"},{\"lang\":\"2\",\"title\":\"TPC\",\"content\":\"这是数据库页面文案，正式上线请替换为正式文本\"},{\"lang\":\"3\",\"title\":\"TPC\",\"content\":\"这是数据库页面文案，正式上线请替换为正式文本\"}]');
INSERT INTO `databases` VALUES (2, '[{\"title_1\":\"文档1\",\"title_2\":\"文档1\",\"title_3\":\"文档1\",\"title_4\":\"文档1\",\"title_5\":\"文档1\",\"title_6\":\"文档1\",\"document\":\"databases\\/documents\\/doc1.pdf\",\"time\":\"2000-01-01 00:00:00\"}]');
INSERT INTO `databases` VALUES (3, '[{\"title_1\":\"海报1\",\"title_2\":\"海报1\",\"title_3\":\"海报1\",\"title_4\":\"海报1\",\"title_5\":\"海报1\",\"title_6\":\"海报1\",\"poster\":\"databases\\/posters\\/poster1.png\",\"time\":\"2000-01-01 00:00:00\"}]');
INSERT INTO `databases` VALUES (4, '[{\"title_1\":\"视频1\",\"title_2\":\"视频1\",\"title_3\":\"视频1\",\"title_4\":\"视频1\",\"title_5\":\"视频1\",\"title_6\":\"视频1\",\"video\":\"databases\\/videos\\/video1.mp4\",\"time\":\"2000-01-01 00:00:00\"}]');
INSERT INTO `databases` VALUES (5, '[{\"title_1\":\"链接1\",\"title_2\":\"链接1\",\"title_3\":\"链接1\",\"title_4\":\"链接1\",\"title_5\":\"链接1\",\"title_6\":\"链接1\",\"link\":\"https:\\/\\/tpc.cc\\/video\\/8888\",\"time\":\"2000-01-01 00:00:00\"}]');

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `failed_jobs_uuid_unique`(`uuid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of failed_jobs
-- ----------------------------

-- ----------------------------
-- Table structure for fee_allocs
-- ----------------------------
DROP TABLE IF EXISTS `fee_allocs`;
CREATE TABLE `fee_allocs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `total_am` decimal(20, 6) UNSIGNED NOT NULL COMMENT '总金额',
  `node2_am` decimal(20, 6) UNSIGNED NOT NULL COMMENT '节点2金额',
  `node2_rate` decimal(6, 2) UNSIGNED NOT NULL COMMENT '节点2比例(%)',
  `node2_cc` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '节点2人数',
  `node2_rem` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '节点2剩余',
  `total_rem` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '总共剩余',
  `status` tinyint(3) UNSIGNED NOT NULL COMMENT '状态 1=进行中 2=已结束 3=失败',
  `sett_date` date NOT NULL COMMENT '结算日期',
  `start_at` datetime NULL DEFAULT NULL COMMENT '开始时间',
  `end_at` datetime NULL DEFAULT NULL COMMENT '结束时间',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `sett_date`(`sett_date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '手续费分配' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of fee_allocs
-- ----------------------------

-- ----------------------------
-- Table structure for fee_logs
-- ----------------------------
DROP TABLE IF EXISTS `fee_logs`;
CREATE TABLE `fee_logs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `type` tinyint(3) UNSIGNED NOT NULL COMMENT '事件类型',
  `amount` decimal(20, 6) UNSIGNED NOT NULL COMMENT '金额',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '手续费日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of fee_logs
-- ----------------------------

-- ----------------------------
-- Table structure for fee_setts
-- ----------------------------
DROP TABLE IF EXISTS `fee_setts`;
CREATE TABLE `fee_setts`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `node_id` int(10) UNSIGNED NOT NULL COMMENT '节点id',
  `type` tinyint(3) UNSIGNED NOT NULL COMMENT '类型',
  `user_count` int(10) UNSIGNED NOT NULL COMMENT '用户数量',
  `total_amount` decimal(20, 6) UNSIGNED NOT NULL COMMENT '总金额',
  `sett_amount` decimal(20, 6) UNSIGNED NOT NULL COMMENT '结算金额',
  `sett_date` date NOT NULL COMMENT '结算日期',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  INDEX `node_id`(`node_id`) USING BTREE,
  INDEX `sett_date`(`sett_date`) USING BTREE,
  INDEX `user_date`(`user_id`, `sett_date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '手续费结算' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of fee_setts
-- ----------------------------

-- ----------------------------
-- Table structure for fees
-- ----------------------------
DROP TABLE IF EXISTS `fees`;
CREATE TABLE `fees`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `amount` decimal(20, 6) UNSIGNED NOT NULL COMMENT '金额',
  `date` date NOT NULL COMMENT '日期',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `fees_date_unique`(`date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '手续费表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of fees
-- ----------------------------

-- ----------------------------
-- Table structure for invite_rewards
-- ----------------------------
DROP TABLE IF EXISTS `invite_rewards`;
CREATE TABLE `invite_rewards`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '上级id',
  `child_id` int(10) UNSIGNED NOT NULL COMMENT '子用户id',
  `order_id` int(10) UNSIGNED NOT NULL COMMENT '订单id',
  `level` tinyint(3) UNSIGNED NOT NULL COMMENT '级别',
  `layer` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '推荐层',
  `has_node` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级持节点',
  `order_am` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '订单金额',
  `reward_am` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '奖励金额',
  `reward_rate` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '比例(%)',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `invite_rewards_user_id_index`(`user_id`) USING BTREE,
  INDEX `invite_rewards_child_id_index`(`child_id`) USING BTREE,
  INDEX `invite_rewards_order_id_index`(`order_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of invite_rewards
-- ----------------------------

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches`  (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `cancelled_at` int(11) NULL DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of job_batches
-- ----------------------------

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED NULL DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `jobs_queue_index`(`queue`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of jobs
-- ----------------------------

-- ----------------------------
-- Table structure for level_histories
-- ----------------------------
DROP TABLE IF EXISTS `level_histories`;
CREATE TABLE `level_histories`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `old_level` int(10) UNSIGNED NOT NULL COMMENT '原等级',
  `new_level` int(10) UNSIGNED NOT NULL COMMENT '新等级',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `level_histories_user_id_index`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of level_histories
-- ----------------------------

-- ----------------------------
-- Table structure for levels
-- ----------------------------
DROP TABLE IF EXISTS `levels`;
CREATE TABLE `levels`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '编号',
  `user_perf` int(10) UNSIGNED NOT NULL COMMENT '个人业绩',
  `team_perf` int(10) UNSIGNED NOT NULL COMMENT '团队业绩',
  `direct_num` int(10) UNSIGNED NOT NULL COMMENT '直推人数',
  `team_num` int(10) UNSIGNED NOT NULL COMMENT '团队人数',
  `weekly_limit_usdt` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '周USDT提现上限',
  `weekly_limit_nox` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '周NOX提现上限',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code_idx`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户等级' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of levels
-- ----------------------------
INSERT INTO `levels` VALUES (1, 'V1', 0, 0, 0, 0, 300.000000, 300.000000, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `levels` VALUES (2, 'V2', 500, 5000, 3, 10, 500.000000, 500.000000, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `levels` VALUES (3, 'V3', 3000, 30000, 5, 10, 1000.000000, 1000.000000, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `levels` VALUES (4, 'V4', 30000, 500000, 10, 50, 3000.000000, 3000.000000, '2000-01-01 00:00:00', '2000-01-01 00:00:00');
INSERT INTO `levels` VALUES (5, 'V5', 0, 1000000, 20, 100, 5000.000000, 5000.000000, '2000-01-01 00:00:00', '2000-01-01 00:00:00');

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 37 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO `migrations` VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO `migrations` VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` VALUES (4, '2016_01_04_173148_create_admin_tables', 2);
INSERT INTO `migrations` VALUES (5, '2020_09_07_090635_create_admin_settings_table', 2);
INSERT INTO `migrations` VALUES (6, '2020_09_22_015815_create_admin_extensions_table', 2);
INSERT INTO `migrations` VALUES (7, '2020_11_01_083237_update_admin_menu_table', 2);
INSERT INTO `migrations` VALUES (8, '2025_08_12_120750_create_personal_access_tokens_table', 2);
INSERT INTO `migrations` VALUES (9, '2026_08_07_000001_create_configs_table', 3);
INSERT INTO `migrations` VALUES (10, '2026_08_07_000002_create_user_datas_table', 3);
INSERT INTO `migrations` VALUES (11, '2026_08_07_000003_create_user_relation_closures_table', 3);
INSERT INTO `migrations` VALUES (12, '2026_08_07_000004_create_asset_logs_table', 3);
INSERT INTO `migrations` VALUES (13, '2026_08_07_000005_create_levels_table', 3);
INSERT INTO `migrations` VALUES (14, '2026_08_07_000006_create_level_histories_table', 3);
INSERT INTO `migrations` VALUES (15, '2026_08_07_000007_create_user_perf_logs_table', 3);
INSERT INTO `migrations` VALUES (16, '2026_08_07_000008_create_user_power_logs_table', 3);
INSERT INTO `migrations` VALUES (17, '2026_08_07_000009_add_business_columns_to_users_table', 3);
INSERT INTO `migrations` VALUES (18, '2026_08_30_000001_create_chains_table', 3);
INSERT INTO `migrations` VALUES (19, '2026_08_30_000002_create_assets_table', 3);
INSERT INTO `migrations` VALUES (20, '2026_08_30_000003_create_pay_orders_table', 3);
INSERT INTO `migrations` VALUES (21, '2026_08_31_000001_create_alpha_products_table', 4);
INSERT INTO `migrations` VALUES (22, '2026_08_31_000002_create_alpha_orders_table', 4);
INSERT INTO `migrations` VALUES (23, '2026_08_31_000003_create_alpha_rewards_table', 4);
INSERT INTO `migrations` VALUES (24, '2026_08_31_000004_add_alpha_quota_columns_to_user_datas_table', 4);
INSERT INTO `migrations` VALUES (25, '2026_08_31_000005_insert_alpha_quota_config', 4);
INSERT INTO `migrations` VALUES (26, '2026_09_01_000001_create_invite_rewards_table', 5);
INSERT INTO `migrations` VALUES (27, '2026_09_01_000001_create_withdraws_table', 5);
INSERT INTO `migrations` VALUES (28, '2026_09_01_000002_create_fees_table', 5);
INSERT INTO `migrations` VALUES (29, '2026_09_01_000002_create_team_rewards_table', 5);
INSERT INTO `migrations` VALUES (30, '2026_09_01_000003_create_node_products_table', 5);
INSERT INTO `migrations` VALUES (31, '2026_09_01_000003_create_reward_rates_table', 5);
INSERT INTO `migrations` VALUES (32, '2026_09_01_000004_create_node_orders_table', 5);
INSERT INTO `migrations` VALUES (33, '2026_09_01_000005_create_node_fee_allocations_table', 5);
INSERT INTO `migrations` VALUES (34, '2026_09_01_000006_create_node_fee_logs_table', 5);
INSERT INTO `migrations` VALUES (35, '2026_09_01_000007_insert_node_fee_configs', 5);
INSERT INTO `migrations` VALUES (36, '2026_09_01_000008_seed_node_product', 5);

-- ----------------------------
-- Table structure for node_fee_allocations
-- ----------------------------
DROP TABLE IF EXISTS `node_fee_allocations`;
CREATE TABLE `node_fee_allocations`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sett_date` date NOT NULL COMMENT '结算日',
  `total_fee` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '当日手续费总额',
  `node_fee_am` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '进节点池金额',
  `node_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '活跃节点份数',
  `per_share` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '每份分红额',
  `project_am` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '项目收入',
  `remain` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '未分配余量',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=进行中 2=已结束 3=失败',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `node_fee_allocations_sett_date_unique`(`sett_date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of node_fee_allocations
-- ----------------------------

-- ----------------------------
-- Table structure for node_fee_logs
-- ----------------------------
DROP TABLE IF EXISTS `node_fee_logs`;
CREATE TABLE `node_fee_logs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `alloc_id` int(10) UNSIGNED NOT NULL COMMENT '分配记录id',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `am` decimal(20, 6) UNSIGNED NOT NULL COMMENT '该用户分红金额',
  `node_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '该用户节点数量',
  `total_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '本次全局节点总数',
  `total_am` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '分红总金额(池金额)',
  `per_share` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '每份分红额',
  `sett_date` date NULL DEFAULT NULL COMMENT '结算日期',
  `created_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `node_fee_logs_alloc_id_user_id_unique`(`alloc_id`, `user_id`) USING BTREE,
  UNIQUE INDEX `node_fee_logs_sett_date_user_id_unique`(`sett_date`, `user_id`) USING BTREE,
  INDEX `node_fee_logs_user_id_index`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of node_fee_logs
-- ----------------------------

-- ----------------------------
-- Table structure for node_orders
-- ----------------------------
DROP TABLE IF EXISTS `node_orders`;
CREATE TABLE `node_orders`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `product_id` int(10) UNSIGNED NOT NULL COMMENT '产品id',
  `sn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '单号',
  `num` int(10) UNSIGNED NOT NULL COMMENT '数量',
  `price` decimal(20, 6) UNSIGNED NOT NULL COMMENT '单价',
  `amount` decimal(20, 6) UNSIGNED NOT NULL COMMENT '本金',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=待支付 1=持有中 2=取消',
  `pay_at` datetime NULL DEFAULT NULL,
  `fail_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `node_orders_sn_unique`(`sn`) USING BTREE,
  INDEX `node_orders_user_id_index`(`user_id`) USING BTREE,
  INDEX `node_orders_status_index`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of node_orders
-- ----------------------------

-- ----------------------------
-- Table structure for node_products
-- ----------------------------
DROP TABLE IF EXISTS `node_products`;
CREATE TABLE `node_products`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '节点名称',
  `price` decimal(20, 6) UNSIGNED NOT NULL COMMENT '价格',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=启用 0=停用',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of node_products
-- ----------------------------
INSERT INTO `node_products` VALUES (1, '算力加速节点', 1000.000000, 1, 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00');

-- ----------------------------
-- Table structure for partners
-- ----------------------------
DROP TABLE IF EXISTS `partners`;
CREATE TABLE `partners`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '机构名称',
  `logo_url` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '机构图标URL',
  `is_show` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否显示',
  `order` int(11) NOT NULL DEFAULT 0 COMMENT '排序权重',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` datetime NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_name`(`name`) USING BTREE,
  INDEX `idx_active_order`(`is_show`, `order`) USING BTREE,
  INDEX `idx_sort_order`(`order`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '合作机构' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of partners
-- ----------------------------
INSERT INTO `partners` VALUES (1, 'Binance', 'default/partners/Binance.png', 1, 1, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `partners` VALUES (2, 'Bitcoin', 'default/partners/Bitcoin.png', 1, 2, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `partners` VALUES (3, 'Coinbase', 'default/partners/Coinbase.png', 1, 3, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `partners` VALUES (4, 'Crypto', 'default/partners/Crypto.png', 1, 4, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `partners` VALUES (5, 'Dash', 'default/partners/Dash.png', 1, 5, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `partners` VALUES (6, 'Kraken', 'default/partners/Kraken.png', 1, 6, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `partners` VALUES (7, 'Monero', 'default/partners/Monero.png', 1, 7, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `partners` VALUES (8, 'Revolut', 'default/partners/Revolut.png', 1, 8, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `partners` VALUES (9, 'Robinhood', 'default/partners/Robinhood.png', 1, 9, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `partners` VALUES (10, 'SHIBAINU', 'default/partners/SHIBAINU.png', 1, 10, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `partners` VALUES (11, 'Uniswap', 'default/partners/Uniswap.png', 1, 11, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);
INSERT INTO `partners` VALUES (12, 'Upbit', 'default/partners/Upbit.png', 1, 12, '2000-01-01 00:00:00', '2000-01-01 00:00:00', NULL);

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens`  (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of password_reset_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for pay_orders
-- ----------------------------
DROP TABLE IF EXISTS `pay_orders`;
CREATE TABLE `pay_orders`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `sn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '支付单号',
  `order_id` int(10) UNSIGNED NOT NULL COMMENT '订单id',
  `type` tinyint(3) UNSIGNED NOT NULL COMMENT '类型',
  `amount` decimal(16, 6) NOT NULL COMMENT '总金额',
  `balance` decimal(16, 6) NOT NULL DEFAULT 0.000000 COMMENT '余额支付',
  `decimals` tinyint(3) UNSIGNED NOT NULL COMMENT '金额小数位',
  `status` tinyint(3) UNSIGNED NOT NULL COMMENT '支付状态 0=待支付 1=已支付 2=支付取消 3=支付失败 4=支付超时',
  `pay_total` decimal(16, 6) NULL DEFAULT NULL COMMENT '支付金额',
  `symbol` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '资产类型',
  `hash` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '交易哈希',
  `pay_contract` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '支付合约',
  `pay_addr` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '付款地址',
  `token_addr` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '代币地址',
  `dest_addr` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '收款地址',
  `fail_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '失败原因',
  `try_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
  `last_time` datetime NULL DEFAULT NULL COMMENT '超时时间',
  `pay_at` datetime NULL DEFAULT NULL COMMENT '成功时间',
  `fail_at` datetime NULL DEFAULT NULL COMMENT '失败或超时时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `pay_orders_sn_unique`(`sn`) USING BTREE,
  UNIQUE INDEX `pay_orders_order_id_type_unique`(`order_id`, `type`) USING BTREE,
  UNIQUE INDEX `pay_orders_hash_unique`(`hash`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of pay_orders
-- ----------------------------

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `personal_access_tokens_token_unique`(`token`) USING BTREE,
  INDEX `personal_access_tokens_tokenable_type_tokenable_id_index`(`tokenable_type`, `tokenable_id`) USING BTREE,
  INDEX `personal_access_tokens_expires_at_index`(`expires_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of personal_access_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for recharges
-- ----------------------------
DROP TABLE IF EXISTS `recharges`;
CREATE TABLE `recharges`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `sn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '单号',
  `symbol` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '资产类型',
  `amount` decimal(20, 6) NOT NULL DEFAULT 0.000000 COMMENT '充值金额',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 0=未支付 1=已支付 2=已取消 3=失败 4=超时',
  `pay_at` datetime NULL DEFAULT NULL COMMENT '支付时间',
  `fail_at` datetime NULL DEFAULT NULL COMMENT '失败时间',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `sn`(`sn`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '充值' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of recharges
-- ----------------------------

-- ----------------------------
-- Table structure for reward_rates
-- ----------------------------
DROP TABLE IF EXISTS `reward_rates`;
CREATE TABLE `reward_rates`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'invite/team',
  `rank` tinyint(3) UNSIGNED NOT NULL COMMENT 'V等级',
  `has_node` tinyint(3) UNSIGNED NOT NULL COMMENT '0=基础 1=持节点',
  `layer` tinyint(3) UNSIGNED NOT NULL COMMENT '推荐层',
  `rate` decimal(10, 2) UNSIGNED NOT NULL COMMENT '奖励率(%)',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `reward_rates_type_rank_has_node_layer_unique`(`type`, `rank`, `has_node`, `layer`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 77 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of reward_rates
-- ----------------------------
INSERT INTO `reward_rates` VALUES (1, 'invite', 1, 0, 1, 2.00, '2026-09-02 11:26:33', '2026-09-07 10:30:28');
INSERT INTO `reward_rates` VALUES (2, 'invite', 1, 0, 2, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (3, 'invite', 1, 0, 3, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (4, 'invite', 1, 1, 1, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (5, 'invite', 1, 1, 2, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (6, 'invite', 1, 1, 3, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (7, 'invite', 2, 0, 1, 5.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (8, 'invite', 2, 0, 2, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (9, 'invite', 2, 0, 3, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (10, 'invite', 2, 1, 1, 7.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (11, 'invite', 2, 1, 2, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (12, 'invite', 2, 1, 3, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (13, 'invite', 3, 0, 1, 7.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (14, 'invite', 3, 0, 2, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (15, 'invite', 3, 0, 3, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (16, 'invite', 3, 0, 4, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (17, 'invite', 3, 1, 1, 10.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (18, 'invite', 3, 1, 2, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (19, 'invite', 3, 1, 3, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (20, 'invite', 3, 1, 4, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (21, 'invite', 4, 0, 1, 7.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (22, 'invite', 4, 0, 2, 4.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (23, 'invite', 4, 0, 3, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (24, 'invite', 4, 0, 4, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (25, 'invite', 4, 1, 1, 10.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (26, 'invite', 4, 1, 2, 5.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (27, 'invite', 4, 1, 3, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (28, 'invite', 4, 1, 4, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (29, 'invite', 5, 0, 1, 8.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (30, 'invite', 5, 0, 2, 4.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (31, 'invite', 5, 0, 3, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (32, 'invite', 5, 0, 4, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (33, 'invite', 5, 0, 5, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (34, 'invite', 5, 1, 1, 12.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (35, 'invite', 5, 1, 2, 5.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (36, 'invite', 5, 1, 3, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (37, 'invite', 5, 1, 4, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (38, 'invite', 5, 1, 5, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (39, 'team', 1, 0, 1, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (40, 'team', 1, 0, 2, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (41, 'team', 1, 0, 3, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (42, 'team', 1, 1, 1, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (43, 'team', 1, 1, 2, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (44, 'team', 1, 1, 3, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (45, 'team', 2, 0, 1, 4.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (46, 'team', 2, 0, 2, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (47, 'team', 2, 0, 3, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (48, 'team', 2, 1, 1, 5.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (49, 'team', 2, 1, 2, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (50, 'team', 2, 1, 3, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (51, 'team', 3, 0, 1, 5.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (52, 'team', 3, 0, 2, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (53, 'team', 3, 0, 3, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (54, 'team', 3, 0, 4, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (55, 'team', 3, 1, 1, 7.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (56, 'team', 3, 1, 2, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (57, 'team', 3, 1, 3, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (58, 'team', 3, 1, 4, 1.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (59, 'team', 4, 0, 1, 7.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (60, 'team', 4, 0, 2, 4.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (61, 'team', 4, 0, 3, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (62, 'team', 4, 0, 4, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (63, 'team', 4, 1, 1, 10.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (64, 'team', 4, 1, 2, 5.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (65, 'team', 4, 1, 3, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (66, 'team', 4, 1, 4, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (67, 'team', 5, 0, 1, 7.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (68, 'team', 5, 0, 2, 5.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (69, 'team', 5, 0, 3, 4.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (70, 'team', 5, 0, 4, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (71, 'team', 5, 0, 5, 2.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (72, 'team', 5, 1, 1, 10.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (73, 'team', 5, 1, 2, 7.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (74, 'team', 5, 1, 3, 5.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (75, 'team', 5, 1, 4, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');
INSERT INTO `reward_rates` VALUES (76, 'team', 5, 1, 5, 3.00, '2026-09-02 11:26:33', '2026-09-02 11:26:33');

-- ----------------------------
-- Table structure for sessions
-- ----------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions`  (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `sessions_user_id_index`(`user_id`) USING BTREE,
  INDEX `sessions_last_activity_index`(`last_activity`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of sessions
-- ----------------------------

-- ----------------------------
-- Table structure for sys_recharges
-- ----------------------------
DROP TABLE IF EXISTS `sys_recharges`;
CREATE TABLE `sys_recharges`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `admin_id` int(10) UNSIGNED NOT NULL COMMENT '管理员id',
  `asset` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '资产类型',
  `type` tinyint(3) UNSIGNED NOT NULL COMMENT '类型 1=充值 2=扣款',
  `amount` decimal(20, 6) NOT NULL DEFAULT 0.000000 COMMENT '金额',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '备注',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '充值扣款' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of sys_recharges
-- ----------------------------

-- ----------------------------
-- Table structure for team_rewards
-- ----------------------------
DROP TABLE IF EXISTS `team_rewards`;
CREATE TABLE `team_rewards`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '上级id',
  `child_id` int(10) UNSIGNED NOT NULL COMMENT '子用户id',
  `order_id` int(10) UNSIGNED NOT NULL COMMENT '订单id',
  `level` tinyint(3) UNSIGNED NOT NULL COMMENT '上级等级',
  `prev_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '前一级等级',
  `child_level` tinyint(3) UNSIGNED NOT NULL COMMENT '子用户等级',
  `layer` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '推荐层',
  `has_node` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级持节点',
  `is_same_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '平级奖',
  `order_am` decimal(20, 6) UNSIGNED NOT NULL COMMENT '静态收益额',
  `reward_am` decimal(20, 6) UNSIGNED NOT NULL COMMENT '团队奖金额',
  `reward_rate` decimal(10, 2) UNSIGNED NOT NULL COMMENT '收益率(%)',
  `sett_date` date NOT NULL COMMENT '结算日期',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `team_rewards_user_id_index`(`user_id`) USING BTREE,
  INDEX `team_rewards_child_id_index`(`child_id`) USING BTREE,
  INDEX `team_rewards_order_id_index`(`order_id`) USING BTREE,
  INDEX `team_rewards_sett_date_index`(`sett_date`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of team_rewards
-- ----------------------------

-- ----------------------------
-- Table structure for user_datas
-- ----------------------------
DROP TABLE IF EXISTS `user_datas`;
CREATE TABLE `user_datas`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `node_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '节点id',
  `node_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '节点数量',
  `total_stake` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '累计质押',
  `curr_stake` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '当前质押',
  `total_power` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '累计算力',
  `curr_power` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '当前算力',
  `perf` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '业绩',
  `total_usdt` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '累计USDT',
  `current_usdt` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '当前USDT',
  `freeze_usdt` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '冻结USDT',
  `total_nox` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '累计DTP',
  `current_nox` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '当前DTP',
  `freeze_nox` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '冻结DTP',
  `node_reward` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '节点收益',
  `stake_reward` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '质押收益',
  `invite_reward` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '邀请奖励',
  `team_reward` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '团队奖励',
  `fee_fh` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '手续费分红',
  `node_at` datetime NULL DEFAULT NULL COMMENT '节点持有时间',
  `node_commission_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '累计获得佣金笔数',
  `created_at` datetime NULL DEFAULT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  `current_slot` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '额度槽',
  `alpha_quota_used` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '额度槽已用(C)',
  `alpha_frozen` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '冻结收益',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `user_datas_user_id_unique`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10000 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of user_datas
-- ----------------------------
INSERT INTO `user_datas` VALUES (1, 1, 0, 0, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, NULL, 0, '2000-01-01 00:00:00', '2000-01-01 00:00:00', 0.000000, 0.000000, 0.000000);

-- ----------------------------
-- Table structure for user_perf_logs
-- ----------------------------
DROP TABLE IF EXISTS `user_perf_logs`;
CREATE TABLE `user_perf_logs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `type` tinyint(4) NOT NULL COMMENT '类型',
  `change_type` tinyint(4) NOT NULL COMMENT '变动类型 1=增加 2=减少',
  `amount` decimal(20, 0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '变动金额',
  `before_amount` decimal(20, 0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '变动前',
  `after_amount` decimal(20, 0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '变动后',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '业绩日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of user_perf_logs
-- ----------------------------

-- ----------------------------
-- Table structure for user_power_logs
-- ----------------------------
DROP TABLE IF EXISTS `user_power_logs`;
CREATE TABLE `user_power_logs`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `type` tinyint(4) NOT NULL COMMENT '类型',
  `change_type` tinyint(4) NOT NULL COMMENT '变动类型 1=增加 2=减少',
  `amount` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '变动金额',
  `before_amount` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '变动前',
  `after_amount` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000 COMMENT '变动后',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NOT NULL COMMENT '添加时间',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '算力日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of user_power_logs
-- ----------------------------

-- ----------------------------
-- Table structure for user_relation_closures
-- ----------------------------
DROP TABLE IF EXISTS `user_relation_closures`;
CREATE TABLE `user_relation_closures`  (
  `ancestor_id` int(11) NOT NULL COMMENT '祖先id',
  `descendant_id` int(11) NOT NULL COMMENT '用户id',
  `depth` int(11) NOT NULL COMMENT '层级',
  PRIMARY KEY (`ancestor_id`, `descendant_id`) USING BTREE,
  INDEX `descendant`(`descendant_id`, `depth`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of user_relation_closures
-- ----------------------------
INSERT INTO `user_relation_closures` VALUES (1, 1, 0);

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `pay_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `invited_uid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `invite_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `level_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `nft_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `team_num` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `direct_num` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user_perf` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000,
  `team_perf` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000,
  `big_perf` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000,
  `small_perf` decimal(20, 6) UNSIGNED NOT NULL DEFAULT 0.000000,
  `perf_at` datetime NULL DEFAULT NULL,
  `allow_w_usdt` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `allow_w_nox` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `users_email_unique`(`email`) USING BTREE,
  UNIQUE INDEX `users_address_unique`(`address`) USING BTREE,
  UNIQUE INDEX `users_invite_code_unique`(`invite_code`) USING BTREE,
  INDEX `users_invited_uid_index`(`invited_uid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10000 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, '0x00****0001', '0x0000000000000000000000000000000000000001', NULL, NULL, NULL, NULL, NULL, NULL, '2000-01-01 00:00:00', '2000-01-01 00:00:00', 0, 0, '88888888', 1, 0, '', 0, 0, 0.000000, 0.000000, 0.000000, 0.000000, NULL, 1, 1);

-- ----------------------------
-- Table structure for withdraws
-- ----------------------------
DROP TABLE IF EXISTS `withdraws`;
CREATE TABLE `withdraws`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `sn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '单号',
  `symbol` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户类型',
  `amount` decimal(20, 6) NOT NULL DEFAULT 0.000000 COMMENT '金额',
  `real_am` decimal(20, 6) NOT NULL DEFAULT 0.000000 COMMENT '到手金额',
  `fee` decimal(20, 6) NOT NULL DEFAULT 0.000000 COMMENT '手续费',
  `fee_rate` decimal(20, 2) NOT NULL DEFAULT 0.00 COMMENT '手续费率',
  `back_am` decimal(20, 6) NOT NULL DEFAULT 0.000000 COMMENT '已返还金额',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=待审核 2=转帐中 3=已完成 4=失败 5=已驳回',
  `withdraw_mode` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=审核 2=自动 3=合约',
  `to_addr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户钱包地址',
  `signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '签名',
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '消息',
  `withdraw_contract` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '提现合约',
  `project_signer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '项目方',
  `deadline` datetime NULL DEFAULT NULL COMMENT '签名有效时间',
  `try_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
  `is_back` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已返还',
  `hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '交易凭据',
  `audit_at` datetime NULL DEFAULT NULL,
  `done_at` datetime NULL DEFAULT NULL,
  `fail_at` datetime NULL DEFAULT NULL,
  `back_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `withdraws_sn_unique`(`sn`) USING BTREE,
  UNIQUE INDEX `withdraws_hash_unique`(`hash`) USING BTREE,
  INDEX `withdraws_user_id_index`(`user_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of withdraws
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
