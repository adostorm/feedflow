DELIMITER $$

USE `test`$$

DROP PROCEDURE IF EXISTS `delete_table`$$

CREATE DEFINER=`Jerry`@`localhost` PROCEDURE `delete_table`(
	_dbName VARCHAR(30), 
	_tbPrefix VARCHAR(30),
	_offset SMALLINT,
	_limit SMALLINT,
	_isThis BOOL #删除当前的表，不走后缀
)
BEGIN
	DECLARE p_offset INT DEFAULT 0;
	DECLARE p_limit INT DEFAULT 0;
	DECLARE p_dbName VARCHAR(30) DEFAULT "";
	DECLARE p_tbPrefix VARCHAR(30) DEFAULT "";
	DECLARE p_isThis INT DEFAULT TRUE;
	
	DECLARE p_tbName VARCHAR(30) DEFAULT "";	
	DECLARE p_sql VARCHAR(100) DEFAULT "";	
	DECLARE p_exe_sql VARCHAR(10000) DEFAULT "";
	SET p_offset=_offset;
	SET p_limit=_limit;
	SET p_dbName=_dbName;
	SET p_tbPrefix=_tbPrefix;
	SET p_isThis=_isThis;	
	
	IF p_isThis IS FALSE THEN #删除指定的表
	
		SET p_tbName = p_tbPrefix;
		
		IF EXISTS(SELECT 1 AS t
			FROM information_schema.tables
			WHERE table_schema = p_dbName
			AND table_name = p_tbName) 
		THEN
			SET p_sql = CONCAT("DROP TABLE IF EXISTS ", p_dbName, '.' ,p_tbName);
				
			SET @p_exe_sql=p_sql;
			PREPARE stmt FROM @p_exe_sql;
			EXECUTE stmt; 
		END IF;
		SELECT p_dbName AS '数据库名', p_tbName AS '表名', p_sql AS '最后一条执行的语句';
	
	ELSEIF p_isThis IS TRUE THEN #循环删除前缀表
	
		IF p_limit = 0 OR p_limit < 0 THEN 
			SET p_limit = 1;
		END IF;
   
		REPEAT
		
			SET p_tbName=CONCAT(p_tbPrefix, '_' ,p_offset);
			
			IF EXISTS(SELECT 1 AS t
				FROM information_schema.tables
				WHERE table_schema = p_dbName
				AND table_name = p_tbName) 
			THEN
			
				SET p_sql = CONCAT("DROP TABLE IF EXISTS ", p_dbName, '.' ,p_tbName);
				
				SET @p_exe_sql=p_sql;
				PREPARE stmt FROM @p_exe_sql;
				EXECUTE stmt; 
				
			END IF;
			
			SET p_offset = p_offset + 1;
			
			SET p_limit = p_limit - 1;
		
		UNTIL p_limit = 0 END REPEAT;
		   
		SELECT p_dbName AS '数据库名', p_tbName AS '表名', p_sql AS '最后一条执行的语句';
	
	END IF;
    
END$$

DELIMITER ;