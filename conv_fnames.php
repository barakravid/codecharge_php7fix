<?php

///////////////////////////////////////////////
// updated  2019-11-12
///////////////////////////////////////////////

/// exit if script is not called from command line
if(!php_sapi_name() == "cli")
	exit;


$script_name = basename(__FILE__);

function rec_scandir($path)
{
	global $script_name;

	$file_list = scandir($path);
	foreach($file_list as $f)
	{
		if( preg_match("/^(\.|\.\.)$/",$f)  )
		{
			continue;
		}
		else if( is_dir($path."/".$f) ) 
		{
			if( preg_match('/^ccs/i', $f) ||  preg_match('/^node_modules$/i', $f) )
				continue;
			else
				rec_scandir($path."/".$f);
		}
		else
		{
			if( !preg_match("/^.*[\.]php$/i", $f ))
				continue;
		
			if( $script_name == $f )
			{
			
				continue;
			}
			
		
			fix_fnames($path."/".$f);
		}
	}
}

function select_min_match($matches)
{
	if( sizeof($matches) ==  0 )
		return false;
	$offsets = array();
	foreach($matches  as $n=>$match )
	{
		$offsets[$match[1]] = $n;
	}
	ksort($offsets);
	reset($offsets);
	return current($offsets);
//	var_dump($offsets);

//	return $offsets[0];
}



function fix_fnames($path)
{
	$str_result = "";
//	if(!preg_match('/export_data.php$/i', $path ))
//		return;

	$str = file_get_contents($path);
	$str_len = strlen($str);
	$offset = 0;
	$offset_prev = 0;
	$offset1 = 0;
	$offset2 = 0;
	$change_made = false;
	$replaced_content = "";
	$count = 0;
	$pattern = "/(class[\s\r\n]+[a-z_0-9]+[\n\r\s]+extends[\s\r\n]+[a-z_0-9]+[\s\r\n]*{|class[\s\r\n]+[a-z_0-9]+[\s\r\n]*{)/i";
//	echo "\n".$path;
	$kkk =false;
	while(preg_match( $pattern  , $str , $matches , PREG_OFFSET_CAPTURE, $offset) && $offset < $str_len   )
	{
		$kkk = true;
		//	echo "\n".$path." - ".$offset."\n";;

		// default replacment content is from current offset to end of input

		// get class name
		$res = select_min_match($matches);
		$temp = $matches[$res][0];
		
			//echo __FILE__;
			//var_dump($matches[$res]);

		

		$temp = str_replace(array("\r","\n","{")," ",$temp );
		$temp = str_replace("  "," ",$temp );
		$tokens = explode(" ",$temp );
		$class_name = $tokens[1];

		$parent_class_name = "";
		if( $tokens[2] == "extends" )
		{
			$parent_class_name = $tokens[3];

		}

//echo $class_name."\n";
		$new_offset = $matches[$res][1]; // reset offset to start of matching string
		$offset1 = $new_offset + 1;
		if(strlen($str_result) == 0 )
		{
			$str_result = substr($str, 0,$new_offset);
		}
		

		//get next class position
		if(preg_match( $pattern , $str , $matches1 , PREG_OFFSET_CAPTURE, $offset1)  ) 
		{
			/// location of next match
			$res1 = select_min_match($matches1);
			$next_offset = $matches1[$res1][1];

			$string_to_process = substr($str ,$new_offset , $next_offset - $new_offset );
		}
		else
		{
			// there isn't another class definition - use rest of input string
			$string_to_process = substr($str ,$new_offset );
			$next_offset = $str_len;
		}



		/// replace here
		if( preg_match('/function[ ]+'.$class_name.'[(]/i' , $string_to_process ) )
		{
			$change_made = true;
			$string_to_process = preg_replace('/function[ ]+'.$class_name.'[(]/i', "function __construct(", $string_to_process );
			if( $string_to_process  == NULL )
			{
				echo "<br>error in".$path;
				return;
			}
		}

		/// replace parent constructor
		if( preg_match('/parent::'.$parent_class_name.'[(]/i' , $string_to_process ) )
		{
			$string_to_process = preg_replace('/parent::'.$parent_class_name.'[(]/i', "parent::__construct(", $string_to_process);

			$change_made = true;
			if( $string_to_process  == NULL )
			{
				echo "<br>error in".$path;
				return;
			}
		}

		$offset = $next_offset;
			
//			echo "<br>";
		$str_result .= $string_to_process;
	}
	if( !$kkk )
		$str_result = $str;
	//var_dump($str_result);

	$cnnt = 0;
	$str_result = preg_replace('/([\r\n]+[\s]*)(\$CCSEvents[\s]*);/i', '$1$2 = array();', $str_result,-1,$cnn);
	$cnnt += $cnn;
	$str_result = preg_replace('/(public[\s]+\$CCSEvents[\s]*[;])/i', 'public \$CCSEvents = array();', $str_result,-1,$cnn);
	$cnnt += $cnn;
	$str_result = preg_replace('/(public[\s]+\$CCSEvents[\s]*=[\s]*""[\s]*[;])/i', 'public \$CCSEvents = array();', $str_result,-1,$cnn);
	$cnnt += $cnn;

	$str_result= preg_replace('/([\r\n]+)(\$CCSEvents[\s]*=[\s]*""[\s]*;)/i', '$1\$CCSEvents = array();', $str_result,-1,$cnn);

	$cnnt += $cnn;
	$str_result=preg_replace('/(var[\s]+\$CCSEvents[\s]*=[\s]*""[\s]*[;])/i', 'var \$CCSEvents = array();', $str_result,-1,$cnn);
	$cnnt += $cnn;
	$str_result=preg_replace('/(\$this->CCSEvents[\s]*=[\s]*""[\s]*;)/i', '\$this->CCSEvents = array();', $str_result,-1,$cnn);
	$cnnt += $cnn;

	/// replace each expressions
	$each1_pattern = preg_quote('while (list($key,) = each($searching_array))','/');
	if(preg_match('/Template.php$/i', $path ) && 
		preg_match( '/'.$each1_pattern.'/i' , $str_result)  )
	{
		$str_result=preg_replace('/'.$each1_pattern.'/i', 'foreach( $searching_array as $key=>$dontcare)', $str_result,1,$cnn);
		$cnnt += $cnn;
	}

	$each2_pattern = preg_quote('while(list($key, $value) = each($this->blocks[$block_name]))','/');
	if(preg_match('/Template.php$/i', $path ) && 
		preg_match( '/'.$each2_pattern.'/i' , $str_result)  )
	{
		$str_result=preg_replace('/'.$each2_pattern.'/i', 'foreach( $this->blocks[$block_name] as $key=>$value)', $str_result,1,$cnn);
		$cnnt += $cnn;
	}

	$each3_pattern = preg_quote('while(list($key, $value) = each($this->globals))','/');
	if(preg_match('/Template.php$/i', $path ) && 
		preg_match( '/'.$each3_pattern.'/i' , $str_result)  )
	{
		$str_result=preg_replace('/'.$each3_pattern.'/i', 'foreach( $this->globals as $key=>$value)', $str_result,1,$cnn);
		$cnnt += $cnn;
	}

	//// replace each in Classes.php
	$each4_pattern = preg_quote('while ($blnResult && list ($key, $Parameter) = each ($this->Parameters)) 
      {','/');
	if(preg_match('/Classes.php$/i', $path ) && 
		preg_match( '/'.$each4_pattern.'/i' , $str_result)  )
	{
		$str_result=preg_replace('/'.$each4_pattern.'/i', 'foreach( $this->Parameters as $key=>$Parameter) {
		if(!$blnResult)
			continue;', $str_result,1,$cnn);
		$cnnt += $cnn;
	}


	//// fix casting to int in DB_Adapter::PageCount()
	$each5_pattern = preg_quote('return $this->PageSize && $this->RecordsCount != "CCS not counted" ? ceil($this->RecordsCount','/');

	if(preg_match('/db_adapter.php$/i', $path ) && 
		preg_match( '/'.$each5_pattern.'/i' , $str_result)  )
	{
		$str_result=preg_replace('/'.$each5_pattern.'/i', 'return $this->PageSize && $this->RecordsCount != "CCS not counted" ? ceil((int)$this->RecordsCount', $str_result,1,$cnn);
		$cnnt += $cnn;
	}


	if(preg_match('/(common.php|commonserv.php)$/i', $path ) )
	{
		$str222 = 'function CCGetListValues(&$db, $sql, $where = "", $order_by = "", $bound_column = "", $text_column = "", $dbformat = "", $datatype = "", $errorclass = "", $fieldname = "", $DSType = dsSQL)
{
    $errors = new clsErrors();
    $values = ';
 //"";';
		 $str222_pattern = preg_quote( $str222 , '/' );
		if( preg_match('/'.$str222_pattern.'("")'.'/i',$str_result ))
		{
			$str_result = preg_replace( '/'.$str222_pattern.'("")'.'/i' ,  $str222 . "array()", $str_result,1,$cnn );
			$cnnt += $cnn;
		}

	}



	if( $cnnt> 0 )
		$change_made = true;

	if( strlen($str_result ) > 0 && $change_made)
	{
		file_put_contents($path , $str_result);
	}

}



if( $argc  == 2 )
{
	$file_name = $argv[1];
	if( is_dir($argv[1] ) )
	{
//		$d = str_replace("\\","/", dirname(realpath( $argv[1])));
//		echo $d;
		rec_scandir(str_replace("\\","/", $argv[1]));
	}
	//	rec_scandir(str_replace("\\","/", dirname(realpath( $argv[1]))));
}




?>