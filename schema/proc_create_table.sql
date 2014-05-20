DELIMITER $$

USE `test`$$

DROP PROCEDURE IF EXISTS `create_table`$$

CREATE DEFINER=`Jerry`@`localhost` PROCEDURE `create_table`(
	_dbName VARCHAR(30), 
	_tbPrefix VARCHAR(30),
	_offset SMALLINT,
	_limit SMALLINT,
	_isThis BOOL ,#创建当前的表，不走后缀
	_upsql VARCHAR(2000)
)
BEGIN
	DECLARE p_offset INT DEFAULT 0;
	DECLARE p_limit INT DEFAULT 0;
	DECLARE p_dbName VARCHAR(30) DEFAULT "";
	DECLARE p_tbPrefix VARCHAR(30) DEFAULT "";
	DECLARE p_isThis INT DEFAULT TRUE;
	DECLARE p_upsql VARCHAR(2000) DEFAULT "";
	
	DECLARE p_tbName VARCHAR(30) DEFAULT "";	
	DECLARE p_sql VARCHAR(10000) DEFAULT "";	
	DECLARE p_exe_sql VARCHAR(10000) DEFAULT "";
	DECLARE p_msg VARCHAR(300) DEFAULT "";
	SET p_offset=_offset;
	SET p_limit=_limit;
	SET p_dbName=_dbName;
	SET p_tbPrefix=_tbPrefix;
	SET p_isThis=_isThis;	
	SET p_upsql = _upsql;
	REPEAT
		IF p_isThis IS FALSE THEN
			SET p_tbName = p_tbPrefix;
			SET p_limit = 1;
		ELSEIF p_isThis IS TRUE THEN
			SET p_tbName=CONCAT(p_tbPrefix, '_', p_offset);
		END IF;
		
		IF !EXISTS(SELECT 1 AS t
			FROM information_schema.tables
			WHERE table_schema = p_dbName
			AND table_name = p_tbName) 
		THEN
		
			SET p_sql=CONCAT('CREATE TABLE `', p_dbName , "`.`", p_tbName ,"`", p_upsql);
			
			SET @p_exe_sql=p_sql;
			PREPARE stmt FROM @p_exe_sql;
			EXECUTE stmt;
		ELSE 
						
			SET p_msg = CONCAT("数据库: ", p_dbName, " 已经存在表: ", p_tbName);
			
		END IF;
		
		SET p_offset = p_offset + 1;
		
		SET p_limit = p_limit - 1;
	
	UNTIL p_limit = 0 END REPEAT;
	   
	SELECT p_dbName AS '数据库名', p_tbName AS '表名', p_sql AS '最后一条执行的语句', p_msg AS '提示';
    
END$$

DELIMITER ;