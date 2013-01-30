SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

DROP SCHEMA IF EXISTS `toast` ;
CREATE SCHEMA IF NOT EXISTS `toast` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `toast` ;

-- -----------------------------------------------------
-- Table `toast`.`user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`user` ;

CREATE  TABLE IF NOT EXISTS `toast`.`user` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `username` VARCHAR(128) NOT NULL ,
  `password` VARCHAR(128) NOT NULL ,
  `realname` VARCHAR(128) NOT NULL ,
  `email` VARCHAR(255) NOT NULL ,
  `pinyin` VARCHAR(255) NULL ,
  `abbreviation` VARCHAR(45) NULL ,
  `token` VARCHAR(45) NULL ,
  `role` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `username_UNIQUE` (`username` ASC) ,
  UNIQUE INDEX `email_UNIQUE` (`email` ASC) ,
  INDEX `abbreviate_idx` (`abbreviation` ASC) ,
  INDEX `token_idx` (`token` ASC) ,
  INDEX `status_idx` (`status` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`command`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`command` ;

CREATE  TABLE IF NOT EXISTS `toast`.`command` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `command` TEXT NOT NULL ,
  `desc_info` TEXT NULL ,
  `parser_id` VARCHAR(255) NULL ,
  `mode` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  `created_by` INT NULL ,
  `updated_by` INT NULL ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `mode_idx` (`mode` ASC) ,
  INDEX `status_idx` (`status` ASC) ,
  INDEX `created_by_idx` (`created_by` ASC) ,
  INDEX `updated_by_idx` (`updated_by` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`product`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`product` ;

CREATE  TABLE IF NOT EXISTS `toast`.`product` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(128) NOT NULL ,
  `regress_notice` TEXT NULL ,
  `unit_notice` TEXT NULL ,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  `created_by` INT NULL ,
  `updated_by` INT NULL ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `status_idx` (`status` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`project`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`project` ;

CREATE  TABLE IF NOT EXISTS `toast`.`project` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(128) NULL ,
  `path` TEXT NULL ,
  `module_id` INT NULL ,
  `parent_id` INT NOT NULL DEFAULT 0 ,
  `lft` INT NOT NULL ,
  `rgt` INT NOT NULL ,
  `status` TINYINT NOT NULL DEFAULT 1 ,
  `created_by` INT NULL ,
  `updated_by` INT NULL ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  `product_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_module_product_idx` (`product_id` ASC) ,
  INDEX `lft_idx` (`lft` ASC) ,
  INDEX `rgt_idx` (`rgt` ASC) ,
  INDEX `parent_id_idx` (`parent_id` ASC) ,
  INDEX `status_idx` (`status` ASC) ,
  INDEX `created_by_idx` (`created_by` ASC) ,
  INDEX `updated_by_idx` (`updated_by` ASC) ,
  CONSTRAINT `fk_module_project1`
    FOREIGN KEY (`product_id` )
    REFERENCES `toast`.`product` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`task`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`task` ;

CREATE  TABLE IF NOT EXISTS `toast`.`task` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `responsible` INT NULL ,
  `type` TINYINT NOT NULL DEFAULT 0 ,
  `build` TEXT NULL ,
  `svn_url` TEXT NULL ,
  `report_to` TEXT NULL ,
  `exclusive` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  `cron_time` VARCHAR(45) NULL ,
  `wait_machine` TINYINT UNSIGNED NOT NULL DEFAULT 0 ,
  `report_filter` TINYINT UNSIGNED NOT NULL DEFAULT 0 ,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  `created_by` INT NULL ,
  `updated_by` INT NULL ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  `project_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_task_project_idx` (`project_id` ASC) ,
  INDEX `responsible_idx` (`responsible` ASC) ,
  INDEX `type_idx` (`type` ASC) ,
  INDEX `status_idx` (`status` ASC) ,
  INDEX `created_by_idx` (`created_by` ASC) ,
  INDEX `updated_by_idx` (`updated_by` ASC) ,
  CONSTRAINT `fk_task_module1`
    FOREIGN KEY (`project_id` )
    REFERENCES `toast`.`project` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`machine`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`machine` ;

CREATE  TABLE IF NOT EXISTS `toast`.`machine` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `type` TINYINT NOT NULL DEFAULT 0 ,
  `responsible` INT NULL ,
  `notify` TINYINT NOT NULL DEFAULT 0 ,
  `agent_version` VARCHAR(45) NULL ,
  `desc_info` TEXT NULL ,
  `ip` VARCHAR(45) NULL ,
  `hostname` VARCHAR(45) NULL ,
  `platform` VARCHAR(45) NULL ,
  `kernel` VARCHAR(45) NULL ,
  `os` VARCHAR(45) NULL ,
  `cpu` VARCHAR(45) NULL ,
  `memory` SMALLINT NULL ,
  `disk` SMALLINT NULL ,
  `created_by` INT NULL ,
  `updated_by` INT NULL ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  `status` TINYINT NOT NULL DEFAULT 2 ,
  `product_id` INT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `name_idx` (`name` ASC) ,
  INDEX `type_idx` (`type` ASC) ,
  INDEX `responsible_idx` (`responsible` ASC) ,
  INDEX `created_by_idx` (`created_by` ASC) ,
  INDEX `updated_by_idx` (`updated_by` ASC) ,
  INDEX `fk_machine_product_idx` (`product_id` ASC) ,
  CONSTRAINT `fk_machine_product1`
    FOREIGN KEY (`product_id` )
    REFERENCES `toast`.`product` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`job`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`job` ;

CREATE  TABLE IF NOT EXISTS `toast`.`job` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `sudoer` VARCHAR(45) NOT NULL ,
  `timeout` TINYINT UNSIGNED NOT NULL DEFAULT 60 ,
  `stage_num` TINYINT NOT NULL ,
  `crucial` TINYINT UNSIGNED NOT NULL DEFAULT 0 ,
  `failed_repeat` TINYINT UNSIGNED NOT NULL DEFAULT 0 ,
  `type` TINYINT UNSIGNED NOT NULL DEFAULT 0 ,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  `created_by` INT NULL COMMENT '	' ,
  `updated_by` INT NULL ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  `command_id` INT NULL ,
  `task_id` INT NOT NULL ,
  `machine_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_job_command_idx` (`command_id` ASC) ,
  INDEX `fk_job_task_idx` (`task_id` ASC) ,
  INDEX `fk_job_machine_idx` (`machine_id` ASC) ,
  INDEX `type_idx` (`type` ASC) ,
  INDEX `status_idx` (`status` ASC) ,
  INDEX `created_by_idx` (`created_by` ASC) ,
  INDEX `updated_by_idx` (`updated_by` ASC) ,
  CONSTRAINT `fk_job_command1`
    FOREIGN KEY (`command_id` )
    REFERENCES `toast`.`command` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_job_task1`
    FOREIGN KEY (`task_id` )
    REFERENCES `toast`.`task` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_job_machine1`
    FOREIGN KEY (`machine_id` )
    REFERENCES `toast`.`machine` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`task_run`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`task_run` ;

CREATE  TABLE IF NOT EXISTS `toast`.`task_run` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `dev_log` TEXT NULL ,
  `report_to` TEXT NULL ,
  `case_total_amount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `case_pass_amount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `case_fail_amount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `case_block_amount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `case_skip_amount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `status` SMALLINT UNSIGNED NOT NULL ,
  `result` TINYINT UNSIGNED NOT NULL ,
  `is_last` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  `created_by` INT NULL COMMENT '	' ,
  `updated_by` INT NULL ,
  `start_time` DATETIME NULL ,
  `stop_time` DATETIME NULL ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  `task_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_task_run_task_idx` (`task_id` ASC) ,
  INDEX `status_idx` (`status` ASC) ,
  INDEX `result_idx` (`result` ASC) ,
  INDEX `is_last_idx` (`is_last` ASC) ,
  INDEX `created_by_idx` (`created_by` ASC) ,
  INDEX `updated_by_idx` (`updated_by` ASC) ,
  CONSTRAINT `fk_task_run_task1`
    FOREIGN KEY (`task_id` )
    REFERENCES `toast`.`task` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`command_run`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`command_run` ;

CREATE  TABLE IF NOT EXISTS `toast`.`command_run` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NULL ,
  `sudoer` VARCHAR(45) NOT NULL ,
  `timeout` TINYINT NOT NULL ,
  `stage_num` TINYINT NOT NULL ,
  `status` SMALLINT NOT NULL DEFAULT 100 ,
  `result` TINYINT NOT NULL DEFAULT '9' ,
  `return_code` SMALLINT NULL ,
  `desc_info` TEXT NULL ,
  `build` TEXT NULL ,
  `case_total_amount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `case_pass_amount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `case_fail_amount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `case_block_amount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `case_skip_amount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `cc_result` TEXT NULL ,
  `cc_line_hit` INT NULL ,
  `cc_line_total` INT NULL ,
  `cc_branch_hit` INT NULL ,
  `cc_branch_total` INT NULL ,
  `run_times` TINYINT NOT NULL DEFAULT 1 ,
  `start_time` DATETIME NULL ,
  `stop_time` DATETIME NULL ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  `created_by` INT NULL ,
  `command_id` INT NULL ,
  `job_id` INT NULL ,
  `task_run_id` INT NULL ,
  `machine_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_command_run_command_idx` (`command_id` ASC) ,
  INDEX `fk_run_job_idx` (`job_id` ASC) ,
  INDEX `fk_run_task_run_idx` (`task_run_id` ASC) ,
  INDEX `fk_run_machine_idx` (`machine_id` ASC) ,
  INDEX `stage_num_idx` (`stage_num` ASC) ,
  INDEX `status_idx` (`status` ASC) ,
  INDEX `result_idx` (`result` ASC) ,
  INDEX `created_by_idx` (`created_by` ASC) ,
  CONSTRAINT `fk_command_run_command`
    FOREIGN KEY (`command_id` )
    REFERENCES `toast`.`command` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_run_job1`
    FOREIGN KEY (`job_id` )
    REFERENCES `toast`.`job` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_run_task_run1`
    FOREIGN KEY (`task_run_id` )
    REFERENCES `toast`.`task_run` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_run_machine1`
    FOREIGN KEY (`machine_id` )
    REFERENCES `toast`.`machine` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`test_case`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`test_case` ;

CREATE  TABLE IF NOT EXISTS `toast`.`test_case` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `code_url` TEXT NOT NULL ,
  `func_name` VARCHAR(45) NULL ,
  `framework` TINYINT UNSIGNED NOT NULL ,
  `info` TEXT NULL ,
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  `created_by` INT NULL ,
  `updated_by` INT NULL ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  `project_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_test_case_project_idx` (`project_id` ASC) ,
  INDEX `framework_idx` (`framework` ASC) ,
  INDEX `status_idx` (`status` ASC) ,
  INDEX `created_by_idx` (`created_by` ASC) ,
  INDEX `updated_by_idx` (`updated_by` ASC) ,
  CONSTRAINT `fk_test_case_module1`
    FOREIGN KEY (`project_id` )
    REFERENCES `toast`.`project` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`case_result`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`case_result` ;

CREATE  TABLE IF NOT EXISTS `toast`.`case_result` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `case_name` VARCHAR(255) NOT NULL ,
  `case_info` TEXT NULL ,
  `create_time` DATETIME NULL ,
  `case_result` TINYINT NOT NULL ,
  `is_last` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  `command_run_id` INT NOT NULL ,
  `test_case_id` INT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_case_result_run_idx` (`command_run_id` ASC) ,
  INDEX `fk_case_result_test_case_idx` (`test_case_id` ASC) ,
  INDEX `case_result_idx` (`case_result` ASC) ,
  INDEX `is_last_idx` (`is_last` ASC) ,
  CONSTRAINT `fk_case_result_run1`
    FOREIGN KEY (`command_run_id` )
    REFERENCES `toast`.`command_run` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_case_result_test_case1`
    FOREIGN KEY (`test_case_id` )
    REFERENCES `toast`.`test_case` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`job_test_case`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`job_test_case` ;

CREATE  TABLE IF NOT EXISTS `toast`.`job_test_case` (
  `job_id` INT NOT NULL ,
  `test_case_id` INT NOT NULL ,
  `display_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`job_id`, `test_case_id`) ,
  INDEX `fk_test_case_has_job_job_idx` (`job_id` ASC) ,
  INDEX `fk_test_case_has_job_test_case_idx` (`test_case_id` ASC) ,
  CONSTRAINT `fk_test_case_has_job_test_case1`
    FOREIGN KEY (`test_case_id` )
    REFERENCES `toast`.`test_case` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_case_has_job_job1`
    FOREIGN KEY (`job_id` )
    REFERENCES `toast`.`job` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`product_user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`product_user` ;

CREATE  TABLE IF NOT EXISTS `toast`.`product_user` (
  `product_id` INT NOT NULL AUTO_INCREMENT ,
  `user_id` INT NOT NULL ,
  `role` TINYINT NOT NULL DEFAULT 1 ,
  `status` TINYINT NOT NULL ,
  `created_by` INT NULL ,
  `updated_by` INT NULL ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  PRIMARY KEY (`product_id`, `user_id`) ,
  INDEX `fk_product_user_user_idx` (`user_id` ASC) ,
  INDEX `fk_product_user_product_idx` (`product_id` ASC) ,
  INDEX `role_idx` (`role` ASC) ,
  INDEX `status_idx` (`status` ASC) ,
  CONSTRAINT `fk_project_user_project1`
    FOREIGN KEY (`product_id` )
    REFERENCES `toast`.`product` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_project_user_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `toast`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`query`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`query` ;

CREATE  TABLE IF NOT EXISTS `toast`.`query` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(45) NOT NULL ,
  `query_str` TEXT NOT NULL ,
  `table` VARCHAR(255) NOT NULL ,
  `created_by` INT NULL ,
  `create_time` DATETIME NULL ,
  `update_time` DATETIME NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `created_by_idx` (`created_by` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`report`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`report` ;

CREATE  TABLE IF NOT EXISTS `toast`.`report` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `date` DATE NOT NULL ,
  `task_run_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_report_task_run_idx` (`task_run_id` ASC) ,
  INDEX `date_idx` (`date` ASC) ,
  CONSTRAINT `fk_report_task_run1`
    FOREIGN KEY (`task_run_id` )
    REFERENCES `toast`.`task_run` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`option`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`option` ;

CREATE  TABLE IF NOT EXISTS `toast`.`option` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `key` VARCHAR(45) NOT NULL ,
  `value` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `key_idx` (`key` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`diff_action`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`diff_action` ;

CREATE  TABLE IF NOT EXISTS `toast`.`diff_action` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `model_name` VARCHAR(45) NOT NULL ,
  `model_id` INT NOT NULL ,
  `type` TINYINT NOT NULL DEFAULT 0 ,
  `updated_by` INT NULL ,
  `update_time` DATETIME NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `model_id_idx` (`model_id` ASC) ,
  INDEX `model_name_idx` (`model_name` ASC) ,
  INDEX `updated_by_idx` (`updated_by` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`diff_attribute`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`diff_attribute` ;

CREATE  TABLE IF NOT EXISTS `toast`.`diff_attribute` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `model_name` VARCHAR(45) NOT NULL ,
  `model_id` INT NOT NULL ,
  `attribute` VARCHAR(45) NOT NULL ,
  `old` TEXT NULL ,
  `new` TEXT NULL ,
  `diff_action_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_diff_attribute_diff_action1_idx` (`diff_action_id` ASC) ,
  INDEX `model_name_idx` (`model_name` ASC) ,
  INDEX `modle_id_idx` (`model_id` ASC) ,
  CONSTRAINT `fk_diff_attribute_diff_action1`
    FOREIGN KEY (`diff_action_id` )
    REFERENCES `toast`.`diff_action` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `toast`.`parser`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `toast`.`parser` ;

CREATE  TABLE IF NOT EXISTS `toast`.`parser` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `parser_class` VARCHAR(255) NOT NULL ,
  `desc_info` TEXT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `parser_class_UNIQUE` (`parser_class` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Placeholder table for view `toast`.`vproduct`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `toast`.`vproduct` (`id` INT, `name` INT, `regress_notice` INT, `unit_notice` INT, `status` INT, `created_by` INT, `updated_by` INT, `create_time` INT, `update_time` INT, `created_by_username` INT, `created_by_realname` INT, `updated_by_username` INT, `updated_by_realname` INT);

-- -----------------------------------------------------
-- Placeholder table for view `toast`.`vproject`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `toast`.`vproject` (`id` INT, `name` INT, `path` INT, `module_id` INT, `parent_id` INT, `lft` INT, `rgt` INT, `status` INT, `created_by` INT, `updated_by` INT, `create_time` INT, `update_time` INT, `product_id` INT, `product_name` INT, `created_by_username` INT, `created_by_realname` INT, `updated_by_username` INT, `updated_by_realname` INT);

-- -----------------------------------------------------
-- Placeholder table for view `toast`.`vproduct_user`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `toast`.`vproduct_user` (`product_id` INT, `user_id` INT, `role` INT, `status` INT, `created_by` INT, `updated_by` INT, `create_time` INT, `update_time` INT, `product_name` INT, `username` INT, `realname` INT);

-- -----------------------------------------------------
-- Placeholder table for view `toast`.`vmachine`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `toast`.`vmachine` (`id` INT, `name` INT, `type` INT, `responsible` INT, `notify` INT, `agent_version` INT, `desc_info` INT, `ip` INT, `hostname` INT, `platform` INT, `kernel` INT, `os` INT, `cpu` INT, `memory` INT, `disk` INT, `created_by` INT, `updated_by` INT, `create_time` INT, `update_time` INT, `status` INT, `product_id` INT, `product_name` INT, `created_by_username` INT, `created_by_realname` INT, `updated_by_username` INT, `updated_by_realname` INT, `responsible_username` INT, `responsible_realname` INT);

-- -----------------------------------------------------
-- Placeholder table for view `toast`.`vcommand`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `toast`.`vcommand` (`id` INT, `name` INT, `command` INT, `desc_info` INT, `parser_id` INT, `mode` INT, `status` INT, `created_by` INT, `updated_by` INT, `create_time` INT, `update_time` INT, `created_by_username` INT, `created_by_realname` INT, `updated_by_username` INT, `updated_by_realname` INT);

-- -----------------------------------------------------
-- Placeholder table for view `toast`.`vcommand_run`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `toast`.`vcommand_run` (`id` INT, `name` INT, `sudoer` INT, `timeout` INT, `stage_num` INT, `status` INT, `result` INT, `return_code` INT, `desc_info` INT, `build` INT, `case_total_amount` INT, `case_pass_amount` INT, `case_fail_amount` INT, `case_block_amount` INT, `case_skip_amount` INT, `cc_result` INT, `cc_line_hit` INT, `cc_line_total` INT, `cc_branch_hit` INT, `cc_branch_total` INT, `run_times` INT, `start_time` INT, `stop_time` INT, `create_time` INT, `update_time` INT, `created_by` INT, `command_id` INT, `job_id` INT, `task_run_id` INT, `machine_id` INT, `command_name` INT, `machine_name` INT, `machine_status` INT, `created_by_username` INT, `created_by_realname` INT);

-- -----------------------------------------------------
-- Placeholder table for view `toast`.`vtask`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `toast`.`vtask` (`id` INT, `name` INT, `responsible` INT, `type` INT, `build` INT, `svn_url` INT, `report_to` INT, `exclusive` INT, `cron_time` INT, `wait_machine` INT, `report_filter` INT, `status` INT, `created_by` INT, `updated_by` INT, `create_time` INT, `update_time` INT, `project_id` INT, `product_id` INT, `product_name` INT, `project_name` INT, `project_path` INT, `created_by_username` INT, `created_by_realname` INT, `updated_by_username` INT, `updated_by_realname` INT, `responsible_username` INT, `responsible_realname` INT);

-- -----------------------------------------------------
-- Placeholder table for view `toast`.`vtask_run`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `toast`.`vtask_run` (`id` INT, `name` INT, `dev_log` INT, `report_to` INT, `case_total_amount` INT, `case_pass_amount` INT, `case_fail_amount` INT, `case_block_amount` INT, `case_skip_amount` INT, `status` INT, `result` INT, `is_last` INT, `created_by` INT, `updated_by` INT, `start_time` INT, `stop_time` INT, `create_time` INT, `update_time` INT, `task_id` INT, `product_id` INT, `product_name` INT, `project_path` INT, `project_id` INT, `project_name` INT, `task_name` INT, `created_by_username` INT, `created_by_realname` INT, `updated_by_username` INT, `updated_by_realname` INT);

-- -----------------------------------------------------
-- Placeholder table for view `toast`.`vreport`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `toast`.`vreport` (`id` INT, `date` INT, `task_run_id` INT, `product_id` INT, `product_name` INT, `project_id` INT, `project_name` INT, `project_path` INT, `responsible` INT, `module_id` INT, `module_name` INT, `task_id` INT, `task_name` INT, `task_type` INT, `case_total_amount` INT, `case_pass_amount` INT, `case_fail_amount` INT, `case_block_amount` INT, `case_skip_amount` INT, `status` INT, `result` INT, `responsible_username` INT, `responsible_realname` INT);

-- -----------------------------------------------------
-- Placeholder table for view `toast`.`vtest_case`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `toast`.`vtest_case` (`id` INT, `name` INT, `code_url` INT, `func_name` INT, `framework` INT, `info` INT, `status` INT, `created_by` INT, `updated_by` INT, `create_time` INT, `update_time` INT, `project_id` INT, `project_name` INT, `product_name` INT, `product_id` INT, `project_path` INT, `created_by_username` INT, `created_by_realname` INT, `updated_by_username` INT, `updated_by_realname` INT);

-- -----------------------------------------------------
-- View `toast`.`vproduct`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `toast`.`vproduct` ;
DROP TABLE IF EXISTS `toast`.`vproduct`;
USE `toast`;
CREATE  OR REPLACE VIEW `vproduct` AS
SElECT
`product`.*,
`uc`.`username` AS `created_by_username`,
`uc`.`realname` AS `created_by_realname`,
`uu`.`username` AS `updated_by_username`,
`uu`.`realname` AS `updated_by_realname`
FROM
`product`
LEFT JOIN `user` `uc` ON (`product`.`created_by` = `uc`.`id`)
LEFT JOIN `user` `uu` ON (`product`.`updated_by` = `uu`.`id`);
;

-- -----------------------------------------------------
-- View `toast`.`vproject`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `toast`.`vproject` ;
DROP TABLE IF EXISTS `toast`.`vproject`;
USE `toast`;
CREATE  OR REPLACE VIEW `vproject` AS
SElECT
`project`.*,
`product`.`name` AS `product_name`,
`uc`.`username` AS `created_by_username`,
`uc`.`realname` AS `created_by_realname`,
`uu`.`username` AS `updated_by_username`,
`uu`.`realname` AS `updated_by_realname`
FROM
`project`
LEFT JOIN `user` `uc` ON (`project`.`created_by` = `uc`.`id`)
LEFT JOIN `user` `uu` ON (`project`.`updated_by` = `uu`.`id`)
LEFT JOIN `product` ON (`project`.`product_id` = `product`.`id`);

-- -----------------------------------------------------
-- View `toast`.`vproduct_user`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `toast`.`vproduct_user` ;
DROP TABLE IF EXISTS `toast`.`vproduct_user`;
USE `toast`;
CREATE  OR REPLACE VIEW `vproduct_user` AS
SElECT
`product_user`.*,
`product`.`name` AS `product_name`,
`up`.`username` AS `username`,
`up`.`realname` AS `realname`
FROM
`product_user`
LEFT JOIN `user` `up` ON (`product_user`.`user_id` = `up`.`id`)
LEFT JOIN `product` ON (`product_user`.`product_id` = `product`.`id`);

-- -----------------------------------------------------
-- View `toast`.`vmachine`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `toast`.`vmachine` ;
DROP TABLE IF EXISTS `toast`.`vmachine`;
USE `toast`;
CREATE  OR REPLACE VIEW `vmachine` AS
SElECT 
`machine`.*,
`product`.`name` AS `product_name`,
`uc`.`username` AS `created_by_username`,
`uc`.`realname` AS `created_by_realname`,
`uu`.`username` AS `updated_by_username`,
`uu`.`realname` AS `updated_by_realname`,
`ur`.`username` AS `responsible_username`,
`ur`.`realname` AS `responsible_realname`
FROM
`machine`
LEFT JOIN `user` `uc` ON (`machine`.`created_by` = `uc`.`id`)
LEFT JOIN `user` `uu` ON (`machine`.`updated_by` = `uu`.`id`)
LEFT JOIN `user` `ur` ON (`machine`.`responsible` = `ur`.`id`)
LEFT JOIN `product` ON (`machine`.`product_id` = `product`.`id`);

-- -----------------------------------------------------
-- View `toast`.`vcommand`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `toast`.`vcommand` ;
DROP TABLE IF EXISTS `toast`.`vcommand`;
USE `toast`;
CREATE  OR REPLACE VIEW `vcommand` AS
SElECT 
`command`.*,
`uc`.`username` AS `created_by_username`,
`uc`.`realname` AS `created_by_realname`,
`uu`.`username` AS `updated_by_username`,
`uu`.`realname` AS `updated_by_realname`
FROM
`command`
LEFT JOIN `user` `uc` ON (`command`.`created_by` = `uc`.`id`)
LEFT JOIN `user` `uu` ON (`command`.`updated_by` = `uu`.`id`);

-- -----------------------------------------------------
-- View `toast`.`vcommand_run`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `toast`.`vcommand_run` ;
DROP TABLE IF EXISTS `toast`.`vcommand_run`;
USE `toast`;
CREATE  OR REPLACE VIEW `vcommand_run` AS
SElECT 
`command_run`.*,
`command`.`name` AS `command_name`,
`machine`.`name` AS `machine_name`,
`machine`.`status` AS `machine_status`,
`uc`.`username` AS `created_by_username`,
`uc`.`realname` AS `created_by_realname`
FROM
`command_run`
LEFT JOIN `user` `uc` ON (`command_run`.`created_by` = `uc`.`id`)
LEFT JOIN `command` ON (`command_run`.`command_id` = `command`.`id`)
LEFT JOIN `machine` ON (`command_run`.`machine_id` = `machine`.`id`);

-- -----------------------------------------------------
-- View `toast`.`vtask`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `toast`.`vtask` ;
DROP TABLE IF EXISTS `toast`.`vtask`;
USE `toast`;
CREATE  OR REPLACE VIEW `vtask` AS
SElECT 
`task`.*,
`product`.`id` AS `product_id`,
`product`.`name` AS `product_name`,
`project`.`name` AS `project_name`,
`project`.`path` AS `project_path`,
`uc`.`username` AS `created_by_username`,
`uc`.`realname` AS `created_by_realname`,
`uu`.`username` AS `updated_by_username`,
`uu`.`realname` AS `updated_by_realname`,
`ur`.`username` AS `responsible_username`,
`ur`.`realname` AS `responsible_realname`
FROM
`task`
LEFT JOIN `user` `uc` ON (`task`.`created_by` = `uc`.`id`)
LEFT JOIN `user` `uu` ON (`task`.`updated_by` = `uu`.`id`)
LEFT JOIN `user` `ur` ON (`task`.`responsible` = `ur`.`id`)
LEFT JOIN `project` ON (`task`.`project_id` = `project`.`id`)
LEFT JOIN `product` ON (`project`.`product_id` = `product`.`id`);

-- -----------------------------------------------------
-- View `toast`.`vtask_run`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `toast`.`vtask_run` ;
DROP TABLE IF EXISTS `toast`.`vtask_run`;
USE `toast`;
CREATE  OR REPLACE VIEW `vtask_run` AS
SElECT 
`task_run`.*,
`product`.`id` AS `product_id`,
`product`.`name` AS `product_name`,
`project`.`path` AS `project_path`,
`project`.`id` AS `project_id`,
`project`.`name` AS `project_name`,
`task`.`name` AS `task_name`,
`uc`.`username` AS `created_by_username`,
`uc`.`realname` AS `created_by_realname`,
`uu`.`username` AS `updated_by_username`,
`uu`.`realname` AS `updated_by_realname`
FROM
`task_run`
LEFT JOIN `user` `uc` ON (`task_run`.`created_by` = `uc`.`id`)
LEFT JOIN `user` `uu` ON (`task_run`.`updated_by` = `uu`.`id`)
LEFT JOIN `task` ON (`task_run`.`task_id` = `task`.`id`)
LEFT JOIN `project` ON (`task`.`project_id` = `project`.`id`)
LEFT JOIN `product` ON (`project`.`product_id` = `product`.`id`);

-- -----------------------------------------------------
-- View `toast`.`vreport`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `toast`.`vreport` ;
DROP TABLE IF EXISTS `toast`.`vreport`;
USE `toast`;
CREATE  OR REPLACE VIEW `vreport` AS
SELECT
`report`.*,
`product`.`id` AS `product_id`,
`product`.`name` AS `product_name`,
`project`.`id` AS `project_id`,
`project`.`name` AS `project_name`,
`project`.`path` AS `project_path`,
`task`.`responsible` as `responsible`,
`module`.`id` AS `module_id`,
`module`.`name` AS `module_name`,
`task`.`id` AS `task_id`,
`task`.`name` AS `task_name`,
`task`.`type` AS `task_type`,
`task_run`.`case_total_amount` AS `case_total_amount`,
`task_run`.`case_pass_amount` AS `case_pass_amount`,
`task_run`.`case_fail_amount` AS `case_fail_amount`,
`task_run`.`case_block_amount` AS `case_block_amount`,
`task_run`.`case_skip_amount` AS `case_skip_amount`,
`task_run`.`status` AS `status`,
`task_run`.`result` AS `result`,
`ur`.`username` AS `responsible_username`,
`ur`.`realname` AS `responsible_realname`
FROM
`report`
LEFT JOIN `task_run` ON (`task_run`.`id` = `report`.`task_run_id`)
LEFT JOIN `task`  ON (`task_run`.`task_id` = `task`.`id`) 
LEFT JOIN `user` `ur` ON (`task`.`responsible` = `ur`.`id`)
LEFT JOIN `project` on (`task`.`project_id` = `project`.`id`)
LEFT JOIN `product` on (`project`.`product_id` = `product`.`id`)
LEFT JOIN `project` `module` on (`project`.`module_id` = `module`.`id`);

-- -----------------------------------------------------
-- View `toast`.`vtest_case`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `toast`.`vtest_case` ;
DROP TABLE IF EXISTS `toast`.`vtest_case`;
USE `toast`;
CREATE  OR REPLACE VIEW `vtest_case` AS 
SELECT 
`test_case`.*,
`vproject`.`name` AS `project_name`,
`vproject`.`product_name` AS `product_name`,
`vproject`.`product_id` AS `product_id`,
`vproject`.`path` AS `project_path`,
`uc`.`username` AS `created_by_username`,
`uc`.`realname` AS `created_by_realname`,
`uu`.`username` AS `updated_by_username`,
`uu`.`realname` AS `updated_by_realname`
FROM `test_case`
LEFT JOIN `vproject` ON (`test_case`.`project_id` = `vproject`.`id`)
LEFT JOIN `user` `uc` ON (`test_case`.`created_by` = `uc`.`id`)
LEFT JOIN `user` `uu` ON (`test_case`.`updated_by` = `uu`.`id`);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `toast`.`user`
-- -----------------------------------------------------
START TRANSACTION;
USE `toast`;
INSERT INTO `toast`.`user` (`id`, `username`, `password`, `realname`, `email`, `pinyin`, `abbreviation`, `token`, `role`, `status`, `create_time`, `update_time`) VALUES (NULL, 'Toast', MD5("toast"), 'Toast', 'toast@toast.org', 'Toast', 'Toast', NULL, 1, 1, NOW(), NOW());
INSERT INTO `toast`.`user` (`id`, `username`, `password`, `realname`, `email`, `pinyin`, `abbreviation`, `token`, `role`, `status`, `create_time`, `update_time`) VALUES (NULL, 'Admin', MD5("admin"), 'Admin', 'admin@toast.org', 'Admin', 'Admin', NULL, 2, 1, NOW(), NOW());

COMMIT;

-- -----------------------------------------------------
-- Data for table `toast`.`product`
-- -----------------------------------------------------
START TRANSACTION;
USE `toast`;
INSERT INTO `toast`.`product` (`id`, `name`, `regress_notice`, `unit_notice`, `status`, `created_by`, `updated_by`, `create_time`, `update_time`) VALUES (NULL, 'Sample', NULL, NULL, 1, 2, 2, NOW(), NOW());

COMMIT;

-- -----------------------------------------------------
-- Data for table `toast`.`parser`
-- -----------------------------------------------------
START TRANSACTION;
USE `toast`;
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'GTest-txt', 'GTestTxtParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'GTest-xml', 'GTestXMLParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'Perl', 'PerlParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'PHPUnit-xml', 'PHPUnitXMLParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'CPPUnit', 'CPPUnitParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'JUnit-ant', 'JUnitAntParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'JUnit-orig', 'JUnitOrigParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'JUnit-mvn', 'JUnitMvnParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'Mocha', 'MochaParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'Grails', 'GrailsParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'PyUnit', 'PyUnitParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'Perl Test::Class', 'PerlTestParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'NUnit', 'NUnitParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'ApsaraUnit', 'ApsaraUnitParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'ShellUnit', 'ShellUnitParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'LightFace', 'LightFaceParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'Toast', 'ToastParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'NoseTests', 'NoseTestsParser', NULL);
INSERT INTO `toast`.`parser` (`id`, `name`, `parser_class`, `desc_info`) VALUES (NULL, 'Lua', 'LuaParser', NULL);

COMMIT;
