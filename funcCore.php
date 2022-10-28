<?php
	// 組裝sql語法-非空白字  public
	function merge_sql_string_if_not_empty($column_name, $val)
	{
		$ret = ($val != "") ? " and ".$column_name."='".$val."'" : "";
		return $ret;
	}
?>
