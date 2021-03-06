<?php
/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl (http://www.davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com/
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @version    0.9 (Revision: 113, Date: 2008/03/04 15:01:22)
 * @link       http://dibiphp.com/
 * @package    dibi
 */

if(version_compare(PHP_VERSION,'5.1.0','<')){throw
new
Exception('dibi needs PHP 5.1.0 or newer.');}if(!class_exists('NotImplementedException',FALSE)){class
ArgumentOutOfRangeException
extends
InvalidArgumentException{}class
InvalidStateException
extends
RuntimeException{}class
NotImplementedException
extends
LogicException{}class
NotSupportedException
extends
LogicException{}class
MemberAccessException
extends
LogicException{}class
IOException
extends
RuntimeException{}class
FileNotFoundException
extends
IOException{}class
DirectoryNotFoundException
extends
IOException{}class
AbortException
extends
RuntimeException{}}if(!class_exists('NObject',FALSE)){abstract
class
NObject{final
public
function
getClass(){return
get_class($this);}final
public
function
getReflection(){return
new
ReflectionObject($this);}protected
function
__call($name,$args){if($name===''){throw
new
MemberAccessException("Call to method without name.");}$class=get_class($this);if(self::hasEvent($class,$name)){$list=$this->$name;if(is_array($list)||$list
instanceof
Traversable){foreach($list
as$handler){call_user_func_array($handler,$args);}}return;}$cl=$class;do{if(function_exists($nm=$cl.'_prototype_'.$name)){array_unshift($args,$this);return
call_user_func_array($nm,$args);}}while($cl=get_parent_class($cl));throw
new
MemberAccessException("Call to undefined method $class::$name().");}protected
static
function
__callStatic($name,$args){$class=get_called_class();throw
new
MemberAccessException("Call to undefined static method $class::$name().");}protected
function&__get($name){if($name===''){throw
new
MemberAccessException("Cannot read an property without name.");}$class=get_class($this);$m='get'.$name;if(self::hasAccessor($class,$m)){$val=$this->$m();return$val;}else{throw
new
MemberAccessException("Cannot read an undeclared property $class::\$$name.");}}protected
function
__set($name,$value){if($name===''){throw
new
MemberAccessException('Cannot assign to an property without name.');}$class=get_class($this);if(self::hasAccessor($class,'get'.$name)){$m='set'.$name;if(self::hasAccessor($class,$m)){$this->$m($value);}else{throw
new
MemberAccessException("Cannot assign to a read-only property $class::\$$name.");}}else{throw
new
MemberAccessException("Cannot assign to an undeclared property $class::\$$name.");}}protected
function
__isset($name){return$name!==''&&self::hasAccessor(get_class($this),'get'.$name);}protected
function
__unset($name){$class=get_class($this);throw
new
MemberAccessException("Cannot unset an property $class::\$$name.");}private
static
function
hasAccessor($c,$m){static$cache;if(!isset($cache[$c])){$cache[$c]=array_flip(get_class_methods($c));}$m[3]=$m[3]&"\xDF";return
isset($cache[$c][$m]);}private
static
function
hasEvent($c,$m){return
preg_match('#^on[A-Z]#',$m)&&property_exists($c,$m);}}}if(!interface_exists('IDebuggable',FALSE)){interface
IDebuggable{}}interface
IDibiVariable{public
function
toSql(DibiTranslator$translator,$modifier);}interface
IDataSource
extends
Countable,IteratorAggregate{}interface
IDibiDriver{function
connect(array&$config);function
disconnect();function
query($sql);function
affectedRows();function
insertId($sequence);function
begin();function
commit();function
rollback();function
format($value,$type);function
applyLimit(&$sql,$limit,$offset);function
rowCount();function
seek($row);function
fetch($type);function
free();function
getColumnsMeta();function
getResource();function
getResultResource();function
getDibiReflection();}class
DibiException
extends
Exception{}class
DibiDriverException
extends
DibiException
implements
IDebuggable{private
static$errorMsg;private$sql;public
function
__construct($message=NULL,$code=0,$sql=NULL){parent::__construct($message,(int)$code);$this->sql=$sql;dibi::notify(NULL,'exception',$this);}final
public
function
getSql(){return$this->sql;}public
function
__toString(){return
parent::__toString().($this->sql?"\nSQL: ".$this->sql:'');}public
static
function
tryError(){set_error_handler(array(__CLASS__,'_errorHandler'),E_ALL);self::$errorMsg=NULL;}public
static
function
catchError(&$message){restore_error_handler();$message=self::$errorMsg;self::$errorMsg=NULL;return$message!==NULL;}public
static
function
_errorHandler($code,$message){restore_error_handler();if(ini_get('html_errors')){$message=strip_tags($message);$message=html_entity_decode($message);}self::$errorMsg=$message;}}class
DibiConnection
extends
NObject{private$config;private$driver;private$connected=FALSE;private$inTxn=FALSE;public
function
__construct($config){if(is_string($config)){parse_str($config,$config);}elseif($config
instanceof
IMap){$config=$config->toArray();}if(!isset($config['driver'])){$config['driver']=dibi::$defaultDriver;}$driver=preg_replace('#[^a-z0-9_]#','_',$config['driver']);$class="Dibi".$driver."Driver";if(!class_exists($class,FALSE)){include_once __FILE__."/../../drivers/$driver.php";if(!class_exists($class,FALSE)){throw
new
DibiException("Unable to create instance of dibi driver class '$class'.");}}$this->config=$config;$this->driver=new$class;if(empty($config['lazy'])){$this->connect();}}public
function
__destruct(){$this->disconnect();}final
protected
function
connect(){if(!$this->connected){$this->driver->connect($this->config);$this->connected=TRUE;dibi::notify($this,'connected');}}final
public
function
disconnect(){if($this->connected){if($this->inTxn){$this->rollback();}$this->driver->disconnect();$this->connected=FALSE;dibi::notify($this,'disconnected');}}final
public
function
getConfig($key=NULL,$default=NULL){if($key===NULL){return$this->config;}elseif(isset($this->config[$key])){return$this->config[$key];}else{return$default;}}public
static
function
alias(&$config,$key,$alias=NULL){if(isset($config[$key]))return;if($alias!==NULL&&isset($config[$alias])){$config[$key]=$config[$alias];unset($config[$alias]);}else{$config[$key]=NULL;}}final
public
function
getResource(){return$this->driver->getResource();}final
public
function
query($args){$args=func_get_args();$this->connect();$trans=new
DibiTranslator($this->driver);if($trans->translate($args)){return$this->nativeQuery($trans->sql);}else{throw
new
DibiException('SQL translate error: '.$trans->sql);}}final
public
function
test($args){$args=func_get_args();$this->connect();$trans=new
DibiTranslator($this->driver);$ok=$trans->translate($args);dibi::dump($trans->sql);return$ok;}final
public
function
nativeQuery($sql){$this->connect();dibi::$numOfQueries++;dibi::$sql=$sql;dibi::$elapsedTime=FALSE;$time=-microtime(TRUE);dibi::notify($this,'beforeQuery',$sql);$res=$this->driver->query($sql)?new
DibiResult(clone$this->driver,$this->config):TRUE;$time+=microtime(TRUE);dibi::$elapsedTime=$time;dibi::$totalTime+=$time;dibi::notify($this,'afterQuery',$res);return$res;}public
function
affectedRows(){$rows=$this->driver->affectedRows();if(!is_int($rows)||$rows<0)throw
new
DibiException('Cannot retrieve number of affected rows.');return$rows;}public
function
insertId($sequence=NULL){$id=$this->driver->insertId($sequence);if($id<1)throw
new
DibiException('Cannot retrieve last generated ID.');return(int)$id;}public
function
begin(){$this->connect();if($this->inTxn){throw
new
DibiException('There is already an active transaction.');}$this->driver->begin();$this->inTxn=TRUE;dibi::notify($this,'begin');}public
function
commit(){if(!$this->inTxn){throw
new
DibiException('There is no active transaction.');}$this->driver->commit();$this->inTxn=FALSE;dibi::notify($this,'commit');}public
function
rollback(){if(!$this->inTxn){throw
new
DibiException('There is no active transaction.');}$this->driver->rollback();$this->inTxn=FALSE;dibi::notify($this,'rollback');}public
function
escape($value){$this->connect();return$this->driver->format($value,dibi::FIELD_TEXT);}public
function
delimite($value){return$this->driver->format($value,dibi::IDENTIFIER);}public
function
applyLimit(&$sql,$limit,$offset){$this->driver->applyLimit($sql,$limit,$offset);}public
function
loadFile($file){$this->connect();@set_time_limit(0);$handle=@fopen($file,'r');if(!$handle){throw
new
FileNotFoundException("Cannot open file '$file'.");}$count=0;$sql='';while(!feof($handle)){$s=fgets($handle);$sql.=$s;if(substr(rtrim($s),-1)===';'){$this->driver->query($sql);$sql='';$count++;}}fclose($handle);return$count;}public
function
getDibiReflection(){throw
new
NotImplementedException;}public
function
__wakeup(){throw
new
NotSupportedException('You cannot serialize or unserialize '.$this->getClass().' instances.');}public
function
__sleep(){throw
new
NotSupportedException('You cannot serialize or unserialize '.$this->getClass().' instances.');}}class
DibiResult
extends
NObject
implements
IDataSource{private$driver;private$xlat;private$metaCache;private$fetched=FALSE;private$withTables=FALSE;public$asObjects=FALSE;private
static$types=array(dibi::FIELD_TEXT=>'string',dibi::FIELD_BINARY=>'string',dibi::FIELD_INTEGER=>'int',dibi::FIELD_FLOAT=>'float',dibi::FIELD_COUNTER=>'int',);public
function
__construct($driver,$config){$this->driver=$driver;$this->setWithTables(!empty($config['result:withtables']));$this->asObjects=!empty($config['result:objects']);}public
function
__destruct(){@$this->free();}final
public
function
getResource(){return$this->getDriver()->getResultResource();}final
public
function
seek($row){return($row!==0||$this->fetched)?(bool)$this->getDriver()->seek($row):TRUE;}final
public
function
rowCount(){return$this->getDriver()->rowCount();}final
public
function
free(){if($this->driver!==NULL){$this->driver->free();$this->driver=NULL;}}final
public
function
setWithTables($val){if($val){if($this->metaCache===NULL){$this->metaCache=$this->getDriver()->getColumnsMeta();}$cols=array();foreach($this->metaCache
as$col){$name=$col['table']==''?$col['name']:($col['table'].'.'.$col['name']);if(isset($cols[$name])){$fix=1;while(isset($cols[$name.'#'.$fix]))$fix++;$name.='#'.$fix;}$cols[$name]=TRUE;}$this->withTables=array_keys($cols);}else{$this->withTables=FALSE;}}final
public
function
getWithTables(){return(bool)$this->withTables;}final
public
function
fetch($asObject=NULL){if($this->withTables===FALSE){$row=$this->getDriver()->fetch(TRUE);if(!is_array($row))return
FALSE;}else{$row=$this->getDriver()->fetch(FALSE);if(!is_array($row))return
FALSE;$row=array_combine($this->withTables,$row);}$this->fetched=TRUE;if($this->xlat!==NULL){foreach($this->xlat
as$col=>$type){if(isset($row[$col])){$row[$col]=$this->convert($row[$col],$type);}}}if($asObject||($asObject===NULL&&$this->asObjects)){$row=(object)$row;}return$row;}final
function
fetchSingle(){$row=$this->getDriver()->fetch(TRUE);if(!is_array($row))return
FALSE;$this->fetched=TRUE;$value=reset($row);$key=key($row);if(isset($this->xlat[$key])){return$this->convert($value,$this->xlat[$key]);}return$value;}final
function
fetchAll(){$this->seek(0);$row=$this->fetch(FALSE);if(!$row)return
array();$data=array();if(count($row)===1){$key=key($row);do{$data[]=$row[$key];}while($row=$this->fetch(FALSE));}else{if($this->asObjects)$row=(object)$row;do{$data[]=$row;}while($row=$this->fetch());}return$data;}final
function
fetchAssoc($assoc){$this->seek(0);$row=$this->fetch(FALSE);if(!$row)return
array();$data=NULL;$assoc=explode(',',$assoc);foreach($assoc
as$as){if($as!=='#'&&$as!=='='&&$as!=='@'&&!array_key_exists($as,$row)){throw
new
InvalidArgumentException("Unknown column '$as' in associative descriptor.");}}$assoc[]='=';$last=count($assoc)-1;while($assoc[$last]==='='||$assoc[$last]==='@'){$leaf=$assoc[$last];unset($assoc[$last]);$last--;if($last<0){$assoc[]='#';break;}}do{$x=&$data;foreach($assoc
as$i=>$as){if($as==='#'){$x=&$x[];}elseif($as==='='){if($x===NULL){$x=$row;$x=&$x[$assoc[$i+1]];$x=NULL;}else{$x=&$x[$assoc[$i+1]];}}elseif($as==='@'){if($x===NULL){$x=(object)$row;$x=&$x->{$assoc[$i+1]};$x=NULL;}else{$x=&$x->{$assoc[$i+1]};}}else{$x=&$x[$row[$as]];}}if($x===NULL){if($leaf==='=')$x=$row;else$x=(object)$row;}}while($row=$this->fetch(FALSE));unset($x);return$data;}final
function
fetchPairs($key=NULL,$value=NULL){$this->seek(0);$row=$this->fetch(FALSE);if(!$row)return
array();$data=array();if($value===NULL){if($key!==NULL){throw
new
InvalidArgumentException("Either none or both columns must be specified.");}if(count($row)<2){throw
new
UnexpectedValueException("Result must have at least two columns.");}$tmp=array_keys($row);$key=$tmp[0];$value=$tmp[1];}else{if(!array_key_exists($value,$row)){throw
new
InvalidArgumentException("Unknown value column '$value'.");}if($key===NULL){do{$data[]=$row[$value];}while($row=$this->fetch(FALSE));return$data;}if(!array_key_exists($key,$row)){throw
new
InvalidArgumentException("Unknown key column '$key'.");}}do{$data[$row[$key]]=$row[$value];}while($row=$this->fetch(FALSE));return$data;}final
public
function
setType($col,$type=NULL){if(is_array($col)){$this->xlat=$col;}else{$this->xlat[$col]=$type;}}final
public
function
getType($col){return
isset($this->xlat[$col])?$this->xlat[$col]:NULL;}final
public
function
convert($value,$type){if($value===NULL||$value===FALSE){return$value;}if(isset(self::$types[$type])){settype($value,self::$types[$type]);return$value;}if($type===dibi::FIELD_DATE||$type===dibi::FIELD_DATETIME){return
strtotime($value);}if($type===dibi::FIELD_BOOL){return((bool)$value)&&$value!=='f'&&$value!=='F';}return$value;}final
public
function
getColumnsMeta(){if($this->metaCache===NULL){$this->metaCache=$this->getDriver()->getColumnsMeta();}$cols=array();foreach($this->metaCache
as$col){$name=(!$this->withTables||$col['table']===NULL)?$col['name']:($col['table'].'.'.$col['name']);$cols[$name]=$col;}return$cols;}final
public
function
dump(){$none=TRUE;foreach($this
as$i=>$row){if($none){echo"\n<table class=\"dump\">\n<thead>\n\t<tr>\n\t\t<th>#row</th>\n";foreach($row
as$col=>$foo){echo"\t\t<th>".htmlSpecialChars($col)."</th>\n";}echo"\t</tr>\n</thead>\n<tbody>\n";$none=FALSE;}echo"\t<tr>\n\t\t<th>",$i,"</th>\n";foreach($row
as$col){echo"\t\t<td>",htmlSpecialChars($col),"</td>\n";}echo"\t</tr>\n";}if($none){echo'<p><em>empty resultset</em></p>';}else{echo"</tbody>\n</table>\n";}}final
public
function
getIterator($offset=NULL,$limit=NULL){return
new
DibiResultIterator($this,$offset,$limit);}final
public
function
count(){return$this->rowCount();}private
function
getDriver(){if($this->driver===NULL){throw
new
InvalidStateException('Resultset was released from memory.');}return$this->driver;}}final
class
DibiResultIterator
implements
Iterator{private$result;private$offset;private$limit;private$row;private$pointer;public
function
__construct(DibiResult$result,$offset,$limit){$this->result=$result;$this->offset=(int)$offset;$this->limit=$limit===NULL?-1:(int)$limit;}public
function
rewind(){$this->pointer=0;$this->result->seek($this->offset);$this->row=$this->result->fetch();}public
function
key(){return$this->pointer;}public
function
current(){return$this->row;}public
function
next(){$this->row=$this->result->fetch();$this->pointer++;}public
function
valid(){return!empty($this->row)&&($this->limit<0||$this->pointer<$this->limit);}}final
class
DibiTranslator
extends
NObject{public$sql;private$driver;private$cursor;private$args;private$hasError;private$comment;private$ifLevel;private$ifLevelStart;private$limit;private$offset;public
function
__construct(IDibiDriver$driver){$this->driver=$driver;}public
function
getDriver(){return$this->driver;}public
function
translate(array$args){$this->limit=-1;$this->offset=0;$this->hasError=FALSE;$commandIns=NULL;$lastArr=NULL;$cursor=&$this->cursor;$cursor=0;$this->args=array_values($args);$args=&$this->args;$this->ifLevel=$this->ifLevelStart=0;$comment=&$this->comment;$comment=FALSE;$sql=array();while($cursor<count($args)){$arg=$args[$cursor];$cursor++;if(is_string($arg)){$toSkip=strcspn($arg,'`[\'"%');if(strlen($arg)===$toSkip){$sql[]=$arg;}else{$sql[]=substr($arg,0,$toSkip).preg_replace_callback('/(?=`|\[|\'|"|%)(?:`(.+?)`|\[(.+?)\]|(\')((?:\'\'|[^\'])*)\'|(")((?:""|[^"])*)"|(\'|")|%([a-zA-Z]{1,4})(?![a-zA-Z]))/s',array($this,'cb'),substr($arg,$toSkip));}continue;}if($comment){$sql[]='...';continue;}if(is_array($arg)){if(is_string(key($arg))){if($commandIns===NULL){$commandIns=strtoupper(substr(ltrim($args[0]),0,6));$commandIns=$commandIns==='INSERT'||$commandIns==='REPLAC';$sql[]=$this->formatValue($arg,$commandIns?'v':'a');}else{if($lastArr===$cursor-1)$sql[]=',';$sql[]=$this->formatValue($arg,$commandIns?'l':'a');}$lastArr=$cursor;continue;}elseif($cursor===1){$cursor=0;array_splice($args,0,1,$arg);continue;}}$sql[]=$this->formatValue($arg,FALSE);}if($comment)$sql[]="*/";$sql=implode(' ',$sql);if($this->limit>-1||$this->offset>0){$this->driver->applyLimit($sql,$this->limit,$this->offset);}$this->sql=$sql;return!$this->hasError;}public
function
formatValue($value,$modifier){if(is_array($value)){$vx=$kx=array();$separator=', ';switch($modifier){case'and':case'or':$separator=' '.strtoupper($modifier).' ';if(!is_string(key($value))){foreach($value
as$v){$vx[]=$this->formatValue($v,'sql');}return
implode($separator,$vx);}case'a':foreach($value
as$k=>$v){$pair=explode('%',$k,2);$vx[]=$this->delimite($pair[0]).'='.$this->formatValue($v,isset($pair[1])?$pair[1]:FALSE);}return
implode($separator,$vx);case'l':foreach($value
as$k=>$v){$pair=explode('%',$k,2);$vx[]=$this->formatValue($v,isset($pair[1])?$pair[1]:FALSE);}return'('.implode(', ',$vx).')';case'v':foreach($value
as$k=>$v){$pair=explode('%',$k,2);$kx[]=$this->delimite($pair[0]);$vx[]=$this->formatValue($v,isset($pair[1])?$pair[1]:FALSE);}return'('.implode(', ',$kx).') VALUES ('.implode(', ',$vx).')';default:foreach($value
as$v){$vx[]=$this->formatValue($v,$modifier);}return
implode(', ',$vx);}}if($modifier){if($value===NULL){return'NULL';}if($value
instanceof
IDibiVariable){return$value->toSql($this,$modifier);}if(!is_scalar($value)){$this->hasError=TRUE;return'**Unexpected type '.gettype($value).'**';}switch($modifier){case's':return$this->driver->format($value,dibi::FIELD_TEXT);case'sn':return$value==''?'NULL':$this->driver->format($value,dibi::FIELD_TEXT);case'b':return$this->driver->format($value,dibi::FIELD_BOOL);case'i':case'u':if(is_string($value)&&preg_match('#[+-]?\d+(e\d+)?$#A',$value)){return$value;}return(string)(int)($value+0);case'f':if(is_numeric($value)&&(!is_string($value)||strpos($value,'x')===FALSE)){return$value;}return(string)($value+0);case'd':return$this->driver->format(is_string($value)?strtotime($value):$value,dibi::FIELD_DATE);case't':return$this->driver->format(is_string($value)?strtotime($value):$value,dibi::FIELD_DATETIME);case'n':return$this->delimite($value);case'sql':$value=(string)$value;$toSkip=strcspn($value,'`[\'"');if(strlen($value)===$toSkip){return$value;}else{return
substr($value,0,$toSkip).preg_replace_callback('/(?=`|\[|\'|")(?:`(.+?)`|\[(.+?)\]|(\')((?:\'\'|[^\'])*)\'|(")((?:""|[^"])*)"(\'|"))/s',array($this,'cb'),substr($value,$toSkip));}case'a':case'v':$this->hasError=TRUE;return'**Unexpected type '.gettype($value).'**';default:$this->hasError=TRUE;return"**Unknown or invalid modifier %$modifier**";}}if(is_string($value))return$this->driver->format($value,dibi::FIELD_TEXT);if(is_int($value)||is_float($value))return(string)$value;if(is_bool($value))return$this->driver->format($value,dibi::FIELD_BOOL);if($value===NULL)return'NULL';if($value
instanceof
IDibiVariable)return$value->toSql($this,NULL);$this->hasError=TRUE;return'**Unexpected '.gettype($value).'**';}private
function
cb($matches){if(!empty($matches[8])){$mod=$matches[8];$cursor=&$this->cursor;if($cursor>=count($this->args)&&$mod!=='else'&&$mod!=='end'){$this->hasError=TRUE;return"**Extra modifier %$mod**";}if($mod==='if'){$this->ifLevel++;$cursor++;if(!$this->comment&&!$this->args[$cursor-1]){$this->ifLevelStart=$this->ifLevel;$this->comment=TRUE;return"/*";}return'';}elseif($mod==='else'){if($this->ifLevelStart===$this->ifLevel){$this->ifLevelStart=0;$this->comment=FALSE;return"*/";}elseif(!$this->comment){$this->ifLevelStart=$this->ifLevel;$this->comment=TRUE;return"/*";}}elseif($mod==='end'){$this->ifLevel--;if($this->ifLevelStart===$this->ifLevel+1){$this->ifLevelStart=0;$this->comment=FALSE;return"*/";}return'';}elseif($mod==='ex'){array_splice($this->args,$cursor,1,$this->args[$cursor]);return'';}elseif($mod==='lmt'){if($this->args[$cursor]!==NULL)$this->limit=(int)$this->args[$cursor];$cursor++;return'';}elseif($mod==='ofs'){if($this->args[$cursor]!==NULL)$this->offset=(int)$this->args[$cursor];$cursor++;return'';}else{$cursor++;return$this->formatValue($this->args[$cursor-1],$mod);}}if($this->comment)return'...';if($matches[1])return$this->delimite($matches[1]);if($matches[2])return$this->delimite($matches[2]);if($matches[3])return$this->driver->format(str_replace("''","'",$matches[4]),dibi::FIELD_TEXT);if($matches[5])return$this->driver->format(str_replace('""','"',$matches[6]),dibi::FIELD_TEXT);if($matches[7]){$this->hasError=TRUE;return'**Alone quote**';}die('this should be never executed');}private
function
delimite($value){if(strpos($value,':')!==FALSE){$value=strtr($value,dibi::getSubst());}return$this->driver->format($value,dibi::IDENTIFIER);}}final
class
DibiLogger
extends
NObject{private$file;public$logErrors=TRUE;public$logQueries=TRUE;public
function
__construct($file){$this->file=$file;}public
function
handler($connection,$event,$arg){if($event==='afterQuery'&&$this->logQueries){$this->write("OK: ".dibi::$sql.($arg
instanceof
DibiResult?";\n-- rows: ".count($arg):'')."\n-- takes: ".sprintf('%0.3f',dibi::$elapsedTime*1000).' ms'."\n-- driver: ".$connection->getConfig('driver')."\n-- ".date('Y-m-d H:i:s')."\n\n");return;}if($event==='exception'&&$this->logErrors){$message=$arg->getMessage();$code=$arg->getCode();if($code){$message="[$code] $message";}$this->write("ERROR: $message"."\n-- SQL: ".dibi::$sql."\n-- driver: ".";\n-- ".date('Y-m-d H:i:s')."\n\n");return;}}private
function
write($message){$handle=fopen($this->file,'a');if(!$handle)return;flock($handle,LOCK_EX);fwrite($handle,$message);fclose($handle);}}class
DibiVariable
extends
NObject
implements
IDibiVariable{public$value;public$modifier;public
function
__construct($value,$modifier){$this->value=$value;$this->modifier=$modifier;}public
function
toSql(DibiTranslator$translator,$modifier){return$translator->formatValue($this->value,$this->modifier);}}abstract
class
DibiTable
extends
NObject{public
static$primaryMask='id';public
static$lowerCase=TRUE;private$connection;private$options;protected$name;protected$primary;protected$primaryModifier='%i';protected$blankRow=array();public
function
__construct(array$options=array()){$this->options=$options;$this->setup();if($this->connection===NULL){$this->connection=dibi::getConnection();}}public
function
getName(){return$this->name;}public
function
getPrimary(){return$this->primary;}public
function
getConnection(){return$this->connection;}protected
function
setup(){if($this->name===NULL){$name=$this->getClass();if(FALSE!==($pos=strrpos($name,':'))){$name=substr($name,$pos+1);}if(self::$lowerCase){$name=strtolower($name);}$this->name=$name;}if($this->primary===NULL){$this->primary=str_replace(array('%p','%s'),array($this->name,trim($this->name,'s')),self::$primaryMask);}}public
function
insert($data){$this->connection->query('INSERT INTO %n',$this->name,'%v',$this->prepare($data));return$this->connection->insertId();}public
function
update($where,$data){$this->connection->query('UPDATE %n',$this->name,'SET %a',$this->prepare($data),'WHERE %n',$this->primary,'IN ('.$this->primaryModifier,$where,')');return$this->connection->affectedRows();}public
function
delete($where){$this->connection->query('DELETE FROM %n',$this->name,'WHERE %n',$this->primary,'IN ('.$this->primaryModifier,$where,')');return$this->connection->affectedRows();}public
function
find($what){if(!is_array($what)){$what=func_get_args();}return$this->complete($this->connection->query('SELECT * FROM %n',$this->name,'WHERE %n',$this->primary,'IN ('.$this->primaryModifier,$what,')'));}public
function
findAll($order=NULL){if($order===NULL){return$this->complete($this->connection->query('SELECT * FROM %n',$this->name));}else{$order=func_get_args();return$this->complete($this->connection->query('SELECT * FROM %n',$this->name,'ORDER BY %n',$order));}}public
function
fetch($conditions){if(is_array($conditions)){return$this->complete($this->connection->query('SELECT * FROM %n',$this->name,'WHERE %and',$conditions))->fetch();}return$this->complete($this->connection->query('SELECT * FROM %n',$this->name,'WHERE %n='.$this->primaryModifier,$this->primary,$conditions))->fetch();}public
function
createBlank(){$row=$this->blankRow;$row[$this->primary]=NULL;if($this->connection->getConfig('result:objects')){$row=(object)$row;}return$row;}protected
function
prepare($data){if(is_object($data)){return(array)$data;}elseif(is_array($data)){return$data;}throw
new
InvalidArgumentException('Dataset must be array or anonymous object.');}protected
function
complete($res){return$res;}}class
DibiDataSource
extends
NObject
implements
IDataSource{private$connection;private$sql;private$count;public
function
__construct($sql,DibiConnection$connection=NULL){if(strpos($sql,' ')===FALSE){$this->sql=$sql;}else{$this->sql='('.$sql.') AS [source]';}$this->connection=$connection===NULL?dibi::getConnection():$connection;}public
function
getIterator($offset=NULL,$limit=NULL,$cols=NULL){return$this->connection->query('
            SELECT *
            FROM',$this->sql,'
            %ofs %lmt',$offset,$limit);}public
function
count(){if($this->count===NULL){$this->count=$this->connection->query('
                SELECT COUNT(*) FROM',$this->sql)->fetchSingle();}return$this->count;}}class
dibi{const
FIELD_TEXT='s',FIELD_BINARY='S',FIELD_BOOL='b',FIELD_INTEGER='i',FIELD_FLOAT='f',FIELD_DATE='d',FIELD_DATETIME='t',FIELD_UNKNOWN='?',FIELD_COUNTER='C',IDENTIFIER='n',VERSION='0.9 (Revision: 113, Date: 2008/03/04 15:01:22)';private
static$registry=array();private
static$connection;private
static$substs=array();private
static$handlers=array();public
static$sql;public
static$elapsedTime;public
static$totalTime;public
static$numOfQueries=0;public
static$defaultDriver='mysql';final
public
function
__construct(){throw
new
LogicException("Cannot instantiate static class ".get_class($this));}public
static
function
connect($config=array(),$name=0){if(is_array($config)||$config
instanceof
IMap){$config['name']=$name;}else{$config.='&name='.urlencode($name);}return
self::$connection=self::$registry[$name]=new
DibiConnection($config);}public
static
function
disconnect(){self::getConnection()->disconnect();}public
static
function
isConnected(){return(bool)self::$connection;}public
static
function
getConnection($name=NULL){if($name===NULL){if(!self::$connection){throw
new
DibiException('Dibi is not connected to database.');}return
self::$connection;}if(!isset(self::$registry[$name])){throw
new
DibiException("There is no connection named '$name'.");}return
self::$registry[$name];}public
static
function
activate($name){self::$connection=self::getConnection($name);}public
static
function
query($args){$args=func_get_args();return
self::getConnection()->query($args);}public
static
function
nativeQuery($sql){return
self::getConnection()->nativeQuery($sql);}public
static
function
test($args){$args=func_get_args();return
self::getConnection()->test($args);}public
static
function
fetch($args){$args=func_get_args();return
self::getConnection()->query($args)->fetch();}public
static
function
fetchAll($args){$args=func_get_args();return
self::getConnection()->query($args)->fetchAll();}public
static
function
fetchSingle($args){$args=func_get_args();return
self::getConnection()->query($args)->fetchSingle();}public
static
function
affectedRows(){return
self::getConnection()->affectedRows();}public
static
function
insertId($sequence=NULL){return
self::getConnection()->insertId($sequence);}public
static
function
begin(){self::getConnection()->begin();}public
static
function
commit(){self::getConnection()->commit();}public
static
function
rollback(){self::getConnection()->rollback();}public
static
function
loadFile($file){return
self::getConnection()->loadFile($file);}protected
static
function
__callStatic($name,$args){return
call_user_func_array(array(self::getConnection(),$name),$args);}public
static
function
datetime($time=NULL){if($time===NULL){$time=time();}elseif(is_string($time)){$time=strtotime($time);}else{$time=(int)$time;}return
new
DibiVariable($time,dibi::FIELD_DATETIME);}public
static
function
date($date=NULL){$var=self::datetime($date);$var->modifier=dibi::FIELD_DATE;return$var;}public
static
function
addSubst($expr,$subst){self::$substs[':'.$expr.':']=$subst;}public
static
function
removeSubst($expr){if($expr===TRUE){self::$substs=array();}else{unset(self::$substs[':'.$expr.':']);}}public
static
function
getSubst(){return
self::$substs;}public
static
function
addHandler($callback){if(!is_callable($callback)){throw
new
InvalidArgumentException("Invalid callback.");}self::$handlers[]=$callback;}public
static
function
notify(DibiConnection$connection=NULL,$event,$arg=NULL){foreach(self::$handlers
as$handler){call_user_func($handler,$connection,$event,$arg);}}public
static
function
startLogger($file,$logQueries=FALSE){$logger=new
DibiLogger($file);$logger->logQueries=$logQueries;self::addHandler(array($logger,'handler'));return$logger;}public
static
function
dump($sql=NULL,$return=FALSE){ob_start();if($sql
instanceof
DibiResult){$sql->dump();}else{if($sql===NULL)$sql=self::$sql;static$keywords2='ALL|DISTINCT|DISTINCTROW|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|TRUE|FALSE';static$keywords1='SELECT|UPDATE|INSERT(?:\s+INTO)|REPLACE(?:\s+INTO)|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN';$sql=' '.$sql;$sql=preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i","\n\$1",$sql);$sql=preg_replace('# {2,}#',' ',$sql);$sql=wordwrap($sql,100);$sql=htmlSpecialChars($sql);$sql=preg_replace("#\n{2,}#","\n",$sql);$sql=preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is",array('dibi','highlightCallback'),$sql);$sql=trim($sql);echo'<pre class="dump">',$sql,"</pre>\n";}if($return){return
ob_get_clean();}else{ob_end_flush();}}private
static
function
highlightCallback($matches){if(!empty($matches[1]))return'<em style="color:gray">'.$matches[1].'</em>';if(!empty($matches[2]))return'<strong style="color:red">'.$matches[2].'</strong>';if(!empty($matches[3]))return'<strong style="color:blue">'.$matches[3].'</strong>';if(!empty($matches[4]))return'<strong style="color:green">'.$matches[4].'</strong>';}}class
DibiMsSqlDriver
extends
NObject
implements
IDibiDriver{private$connection;private$resultset;public
function
__construct(){if(!extension_loaded('mssql')){throw
new
DibiDriverException("PHP extension 'mssql' is not loaded.");}}public
function
connect(array&$config){DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');DibiConnection::alias($config,'host');if(empty($config['persistent'])){$this->connection=@mssql_connect($config['host'],$config['username'],$config['password'],TRUE);}else{$this->connection=@mssql_pconnect($config['host'],$config['username'],$config['password']);}if(!is_resource($this->connection)){throw
new
DibiDriverException("Can't connect to DB.");}if(isset($config['database'])&&!@mssql_select_db($config['database'],$this->connection)){throw
new
DibiDriverException("Can't select DB '$config[database]'.");}}public
function
disconnect(){mssql_close($this->connection);}public
function
query($sql){$this->resultset=@mssql_query($sql,$this->connection);if($this->resultset===FALSE){throw
new
DibiDriverException('Query error',0,$sql);}return
is_resource($this->resultset);}public
function
affectedRows(){return
mssql_rows_affected($this->connection);}public
function
insertId($sequence){throw
new
NotSupportedException('MS SQL does not support autoincrementing.');}public
function
begin(){$this->query('BEGIN TRANSACTION');}public
function
commit(){$this->query('COMMIT');}public
function
rollback(){$this->query('ROLLBACK');}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return"'".str_replace("'","''",$value)."'";if($type===dibi::IDENTIFIER)return'['.str_replace('.','].[',$value).']';if($type===dibi::FIELD_BOOL)return$value?-1:0;if($type===dibi::FIELD_DATE)return
date("'Y-m-d'",$value);if($type===dibi::FIELD_DATETIME)return
date("'Y-m-d H:i:s'",$value);throw
new
InvalidArgumentException('Unsupported formatting type.');}public
function
applyLimit(&$sql,$limit,$offset){if($limit>=0){$sql='SELECT TOP '.(int)$limit.' * FROM ('.$sql.')';}if($offset){throw
new
NotImplementedException('Offset is not implemented.');}}public
function
rowCount(){return
mssql_num_rows($this->resultset);}public
function
fetch($type){return
mssql_fetch_array($this->resultset,$type?MSSQL_ASSOC:MSSQL_NUM);}public
function
seek($row){return
mssql_data_seek($this->resultset,$row);}public
function
free(){mssql_free_result($this->resultset);$this->resultset=NULL;}public
function
getColumnsMeta(){$count=mssql_num_fields($this->resultset);$meta=array();for($i=0;$i<$count;$i++){$info=(array)mssql_fetch_field($this->resultset,$i);$info['table']=$info['column_source'];$meta[]=$info;}return$meta;}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiMySqlDriver
extends
NObject
implements
IDibiDriver{private$connection;private$resultset;private$buffered;public
function
__construct(){if(!extension_loaded('mysql')){throw
new
DibiDriverException("PHP extension 'mysql' is not loaded.");}}public
function
connect(array&$config){DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');DibiConnection::alias($config,'options');if(!isset($config['username']))$config['username']=ini_get('mysql.default_user');if(!isset($config['password']))$config['password']=ini_get('mysql.default_password');if(!isset($config['host'])){$host=ini_get('mysql.default_host');if($host){$config['host']=$host;$config['port']=ini_get('mysql.default_port');}else{if(!isset($config['socket']))$config['socket']=ini_get('mysql.default_socket');$config['host']=NULL;}}if(empty($config['socket'])){$host=$config['host'].(empty($config['port'])?'':':'.$config['port']);}else{$host=':'.$config['socket'];}if(empty($config['persistent'])){$this->connection=@mysql_connect($host,$config['username'],$config['password'],TRUE,$config['options']);}else{$this->connection=@mysql_pconnect($host,$config['username'],$config['password'],$config['options']);}if(!is_resource($this->connection)){throw
new
DibiDriverException(mysql_error(),mysql_errno());}if(isset($config['charset'])){$ok=FALSE;if(function_exists('mysql_set_charset')){$ok=@mysql_set_charset($config['charset'],$this->connection);}if(!$ok)$ok=@mysql_query("SET NAMES '$config[charset]'",$this->connection);if(!$ok)$this->throwException();}if(isset($config['database'])){@mysql_select_db($config['database'],$this->connection)or$this->throwException();}if(isset($config['sqlmode'])){if(!@mysql_query("SET sql_mode='$config[sqlmode]'",$this->connection))$this->throwException();}$this->buffered=empty($config['unbuffered']);}public
function
disconnect(){mysql_close($this->connection);}public
function
query($sql){if($this->buffered){$this->resultset=@mysql_query($sql,$this->connection);}else{$this->resultset=@mysql_unbuffered_query($sql,$this->connection);}if(mysql_errno($this->connection)){$this->throwException($sql);}return
is_resource($this->resultset);}public
function
affectedRows(){return
mysql_affected_rows($this->connection);}public
function
insertId($sequence){return
mysql_insert_id($this->connection);}public
function
begin(){$this->query('START TRANSACTION');}public
function
commit(){$this->query('COMMIT');}public
function
rollback(){$this->query('ROLLBACK');}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return"'".mysql_real_escape_string($value,$this->connection)."'";if($type===dibi::IDENTIFIER)return'`'.str_replace('.','`.`',$value).'`';if($type===dibi::FIELD_BOOL)return$value?1:0;if($type===dibi::FIELD_DATE)return
date("'Y-m-d'",$value);if($type===dibi::FIELD_DATETIME)return
date("'Y-m-d H:i:s'",$value);throw
new
InvalidArgumentException('Unsupported formatting type.');}public
function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.($limit<0?'18446744073709551615':(int)$limit).($offset>0?' OFFSET '.(int)$offset:'');}public
function
rowCount(){if(!$this->buffered){throw
new
DibiDriverException('Row count is not available for unbuffered queries.');}return
mysql_num_rows($this->resultset);}public
function
fetch($type){return
mysql_fetch_array($this->resultset,$type?MYSQL_ASSOC:MYSQL_NUM);}public
function
seek($row){if(!$this->buffered){throw
new
DibiDriverException('Cannot seek an unbuffered result set.');}return
mysql_data_seek($this->resultset,$row);}public
function
free(){mysql_free_result($this->resultset);$this->resultset=NULL;}public
function
getColumnsMeta(){$count=mysql_num_fields($this->resultset);$meta=array();for($i=0;$i<$count;$i++){$meta[]=(array)mysql_fetch_field($this->resultset,$i);}return$meta;}protected
function
throwException($sql=NULL){throw
new
DibiDriverException(mysql_error($this->connection),mysql_errno($this->connection),$sql);}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiMySqliDriver
extends
NObject
implements
IDibiDriver{private$connection;private$resultset;private$buffered;public
function
__construct(){if(!extension_loaded('mysqli')){throw
new
DibiDriverException("PHP extension 'mysqli' is not loaded.");}}public
function
connect(array&$config){DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');DibiConnection::alias($config,'options');DibiConnection::alias($config,'database');if(!isset($config['username']))$config['username']=ini_get('mysqli.default_user');if(!isset($config['password']))$config['password']=ini_get('mysqli.default_pw');if(!isset($config['socket']))$config['socket']=ini_get('mysqli.default_socket');if(!isset($config['host'])){$config['host']=ini_get('mysqli.default_host');if(!isset($config['port']))$config['port']=ini_get('mysqli.default_port');if(!isset($config['host']))$config['host']='localhost';}$this->connection=mysqli_init();@mysqli_real_connect($this->connection,$config['host'],$config['username'],$config['password'],$config['database'],$config['port'],$config['socket'],$config['options']);if($errno=mysqli_connect_errno()){throw
new
DibiDriverException(mysqli_connect_error(),$errno);}if(isset($config['charset'])){$ok=FALSE;if(version_compare(PHP_VERSION,'5.1.5','>=')){$ok=@mysqli_set_charset($this->connection,$config['charset']);}if(!$ok)$ok=@mysqli_query($this->connection,"SET NAMES '$config[charset]'");if(!$ok)$this->throwException();}if(isset($config['sqlmode'])){if(!@mysqli_query($this->connection,"SET sql_mode='$config[sqlmode]'"))$this->throwException();}$this->buffered=empty($config['unbuffered']);}public
function
disconnect(){mysqli_close($this->connection);}public
function
query($sql){$this->resultset=@mysqli_query($this->connection,$sql,$this->buffered?MYSQLI_STORE_RESULT:MYSQLI_USE_RESULT);if(mysqli_errno($this->connection)){$this->throwException($sql);}return
is_object($this->resultset);}public
function
affectedRows(){return
mysqli_affected_rows($this->connection);}public
function
insertId($sequence){return
mysqli_insert_id($this->connection);}public
function
begin(){$this->query('START TRANSACTION');}public
function
commit(){$this->query('COMMIT');}public
function
rollback(){$this->query('ROLLBACK');}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return"'".mysqli_real_escape_string($this->connection,$value)."'";if($type===dibi::IDENTIFIER)return'`'.str_replace('.','`.`',$value).'`';if($type===dibi::FIELD_BOOL)return$value?1:0;if($type===dibi::FIELD_DATE)return
date("'Y-m-d'",$value);if($type===dibi::FIELD_DATETIME)return
date("'Y-m-d H:i:s'",$value);throw
new
InvalidArgumentException('Unsupported formatting type.');}public
function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.($limit<0?'18446744073709551615':(int)$limit).($offset>0?' OFFSET '.(int)$offset:'');}public
function
rowCount(){if(!$this->buffered){throw
new
DibiDriverException('Row count is not available for unbuffered queries.');}return
mysqli_num_rows($this->resultset);}public
function
fetch($type){return
mysqli_fetch_array($this->resultset,$type?MYSQLI_ASSOC:MYSQLI_NUM);}public
function
seek($row){if(!$this->buffered){throw
new
DibiDriverException('Cannot seek an unbuffered result set.');}return
mysqli_data_seek($this->resultset,$row);}public
function
free(){mysqli_free_result($this->resultset);$this->resultset=NULL;}public
function
getColumnsMeta(){$count=mysqli_num_fields($this->resultset);$meta=array();for($i=0;$i<$count;$i++){$meta[]=(array)mysqli_fetch_field_direct($this->resultset,$i);}return$meta;}protected
function
throwException($sql=NULL){throw
new
DibiDriverException(mysqli_error($this->connection),mysqli_errno($this->connection),$sql);}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiOdbcDriver
extends
NObject
implements
IDibiDriver{private$connection;private$resultset;private$row=0;public
function
__construct(){if(!extension_loaded('odbc')){throw
new
DibiDriverException("PHP extension 'odbc' is not loaded.");}}public
function
connect(array&$config){DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');if(!isset($config['username']))$config['username']=ini_get('odbc.default_user');if(!isset($config['password']))$config['password']=ini_get('odbc.default_pw');if(!isset($config['dsn']))$config['dsn']=ini_get('odbc.default_db');if(empty($config['persistent'])){$this->connection=@odbc_connect($config['dsn'],$config['username'],$config['password']);}else{$this->connection=@odbc_pconnect($config['dsn'],$config['username'],$config['password']);}if(!is_resource($this->connection)){throw
new
DibiDriverException(odbc_errormsg().' '.odbc_error());}}public
function
disconnect(){odbc_close($this->connection);}public
function
query($sql){$this->resultset=@odbc_exec($this->connection,$sql);if($this->resultset===FALSE){$this->throwException($sql);}return
is_resource($this->resultset);}public
function
affectedRows(){return
odbc_num_rows($this->resultset);}public
function
insertId($sequence){throw
new
NotSupportedException('ODBC does not support autoincrementing.');}public
function
begin(){if(!odbc_autocommit($this->connection,FALSE)){$this->throwException();}}public
function
commit(){if(!odbc_commit($this->connection)){$this->throwException();}odbc_autocommit($this->connection,TRUE);}public
function
rollback(){if(!odbc_rollback($this->connection)){$this->throwException();}odbc_autocommit($this->connection,TRUE);}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return"'".str_replace("'","''",$value)."'";if($type===dibi::IDENTIFIER)return'['.str_replace('.','].[',$value).']';if($type===dibi::FIELD_BOOL)return$value?-1:0;if($type===dibi::FIELD_DATE)return
date("#m/d/Y#",$value);if($type===dibi::FIELD_DATETIME)return
date("#m/d/Y H:i:s#",$value);throw
new
InvalidArgumentException('Unsupported formatting type.');}public
function
applyLimit(&$sql,$limit,$offset){if($limit>=0){$sql='SELECT TOP '.(int)$limit.' * FROM ('.$sql.')';}if($offset)throw
new
InvalidArgumentException('Offset is not implemented in driver odbc.');}public
function
rowCount(){return
odbc_num_rows($this->resultset);}public
function
fetch($type){if($type){return
odbc_fetch_array($this->resultset,++$this->row);}else{$set=$this->resultset;if(!odbc_fetch_row($set,++$this->row))return
FALSE;$count=odbc_num_fields($set);$cols=array();for($i=1;$i<=$count;$i++)$cols[]=odbc_result($set,$i);return$cols;}}public
function
seek($row){$this->row=$row;return
TRUE;}public
function
free(){odbc_free_result($this->resultset);$this->resultset=NULL;}public
function
getColumnsMeta(){$count=odbc_num_fields($this->resultset);$meta=array();for($i=1;$i<=$count;$i++){$meta[]=array('name'=>odbc_field_name($this->resultset,$i),'table'=>NULL,'type'=>odbc_field_type($this->resultset,$i),'length'=>odbc_field_len($this->resultset,$i),'scale'=>odbc_field_scale($this->resultset,$i),'precision'=>odbc_field_precision($this->resultset,$i),);}return$meta;}protected
function
throwException($sql=NULL){throw
new
DibiDriverException(odbc_errormsg($this->connection).' '.odbc_error($this->connection),0,$sql);}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiOracleDriver
extends
NObject
implements
IDibiDriver{private$connection;private$resultset;private$autocommit=TRUE;public
function
__construct(){if(!extension_loaded('oci8')){throw
new
DibiDriverException("PHP extension 'oci8' is not loaded.");}}public
function
connect(array&$config){DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');DibiConnection::alias($config,'database','db');DibiConnection::alias($config,'charset');$this->connection=@oci_new_connect($config['username'],$config['password'],$config['database'],$config['charset']);if(!$this->connection){$err=oci_error();throw
new
DibiDriverException($err['message'],$err['code']);}}public
function
disconnect(){oci_close($this->connection);}public
function
query($sql){$this->resultset=oci_parse($this->connection,$sql);if($this->resultset){oci_execute($this->resultset,$this->autocommit?OCI_COMMIT_ON_SUCCESS:OCI_DEFAULT);$err=oci_error($this->resultset);if($err){throw
new
DibiDriverException($err['message'],$err['code'],$sql);}}else{$this->throwException($sql);}return
is_resource($this->resultset);}public
function
affectedRows(){throw
new
NotImplementedException;}public
function
insertId($sequence){throw
new
NotSupportedException('Oracle does not support autoincrementing.');}public
function
begin(){$this->autocommit=FALSE;}public
function
commit(){if(!oci_commit($this->connection)){$this->throwException();}$this->autocommit=TRUE;}public
function
rollback(){if(!oci_rollback($this->connection)){$this->throwException();}$this->autocommit=TRUE;}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return"'".str_replace("'","''",$value)."'";if($type===dibi::IDENTIFIER)return'['.str_replace('.','].[',$value).']';if($type===dibi::FIELD_BOOL)return$value?1:0;if($type===dibi::FIELD_DATE)return
date("U",$value);if($type===dibi::FIELD_DATETIME)return
date("U",$value);throw
new
InvalidArgumentException('Unsupported formatting type.');}public
function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.$limit.($offset>0?' OFFSET '.(int)$offset:'');}public
function
rowCount(){return
oci_num_rows($this->resultset);}public
function
fetch($type){return
oci_fetch_array($this->resultset,($type?OCI_ASSOC:OCI_NUM)|OCI_RETURN_NULLS);}public
function
seek($row){throw
new
NotImplementedException;}public
function
free(){oci_free_statement($this->resultset);$this->resultset=NULL;}public
function
getColumnsMeta(){$count=oci_num_fields($this->resultset);$meta=array();for($i=1;$i<=$count;$i++){$meta[]=array('name'=>oci_field_name($this->resultset,$i),'table'=>NULL,'type'=>oci_field_type($this->resultset,$i),'size'=>oci_field_size($this->resultset,$i),'scale'=>oci_field_scale($this->resultset,$i),'precision'=>oci_field_precision($this->resultset,$i),);}return$meta;}protected
function
throwException($sql=NULL){$err=oci_error($this->connection);throw
new
DibiDriverException($err['message'],$err['code'],$sql);}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiPdoDriver
extends
NObject
implements
IDibiDriver{private$connection;private$resultset;private$affectedRows=FALSE;public
function
__construct(){if(!extension_loaded('pdo')){throw
new
DibiDriverException("PHP extension 'pdo' is not loaded.");}}public
function
connect(array&$config){DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');DibiConnection::alias($config,'dsn');DibiConnection::alias($config,'options');try{$this->connection=new
PDO($config['dsn'],$config['username'],$config['password'],$config['options']);}catch(PDOException$e){throw
new
DibiDriverException($e->getMessage(),$e->getCode());}if(!$this->connection){throw
new
DibiDriverException('Connecting error.');}}public
function
disconnect(){$this->connection=NULL;}public
function
query($sql){$cmd=strtoupper(substr(ltrim($sql),0,6));$list=array('UPDATE'=>1,'DELETE'=>1,'INSERT'=>1,'REPLAC'=>1);if(isset($list[$cmd])){$this->resultset=NULL;$this->affectedRows=$this->connection->exec($sql);if($this->affectedRows===FALSE){$this->throwException($sql);}return
FALSE;}else{$this->resultset=$this->connection->query($sql);$this->affectedRows=FALSE;if($this->resultset===FALSE){$this->throwException($sql);}return
TRUE;}}public
function
affectedRows(){return$this->affectedRows;}public
function
insertId($sequence){return$this->connection->lastInsertId();}public
function
begin(){if(!$this->connection->beginTransaction()){$this->throwException();}}public
function
commit(){if(!$this->connection->commit()){$this->throwException();}}public
function
rollback(){if(!$this->connection->rollBack()){$this->throwException();}}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return$this->connection->quote($value);if($type===dibi::IDENTIFIER)return$value;if($type===dibi::FIELD_BOOL)return$value?1:0;if($type===dibi::FIELD_DATE)return
date("'Y-m-d'",$value);if($type===dibi::FIELD_DATETIME)return
date("'Y-m-d H:i:s'",$value);throw
new
InvalidArgumentException('Unsupported formatting type.');}public
function
applyLimit(&$sql,$limit,$offset){throw
new
NotSupportedException('PDO does not support applying limit or offset.');}public
function
rowCount(){throw
new
DibiDriverException('Row count is not available for unbuffered queries.');}public
function
fetch($type){return$this->resultset->fetch($type?PDO::FETCH_ASSOC:PDO::FETCH_NUM);}public
function
seek($row){throw
new
DibiDriverException('Cannot seek an unbuffered result set.');}public
function
free(){$this->resultset=NULL;}public
function
getColumnsMeta(){$count=$this->resultset->columnCount();$meta=array();for($i=0;$i<$count;$i++){$info=@$this->resultset->getColumnsMeta($i);if($info===FALSE){throw
new
DibiDriverException('Driver does not support meta data.');}$meta[]=$info;}return$meta;}protected
function
throwException($sql=NULL){$err=$this->connection->errorInfo();throw
new
DibiDriverException("SQLSTATE[$err[0]]: $err[2]",$err[1],$sql);}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiPostgreDriver
extends
NObject
implements
IDibiDriver{private$connection;private$resultset;private$escMethod=FALSE;public
function
__construct(){if(!extension_loaded('pgsql')){throw
new
DibiDriverException("PHP extension 'pgsql' is not loaded.");}}public
function
connect(array&$config){if(isset($config['string'])){$string=$config['string'];}else{$string='';foreach(array('host','hostaddr','port','dbname','user','password','connect_timeout','options','sslmode','service')as$key){if(isset($config[$key]))$string.=$key.'='.$config[$key].' ';}}DibiDriverException::tryError();if(isset($config['persistent'])){$this->connection=pg_connect($string,PGSQL_CONNECT_FORCE_NEW);}else{$this->connection=pg_pconnect($string,PGSQL_CONNECT_FORCE_NEW);}if(DibiDriverException::catchError($msg)){throw
new
DibiDriverException($msg,0);}if(!is_resource($this->connection)){throw
new
DibiDriverException('Connecting error.');}if(isset($config['charset'])){DibiDriverException::tryError();pg_set_client_encoding($this->connection,$config['charset']);if(DibiDriverException::catchError($msg)){throw
new
DibiDriverException($msg,0);}}if(isset($config['schema'])){$this->query('SET search_path TO '.$config['schema']);}$this->escMethod=version_compare(PHP_VERSION,'5.2.0','>=');}public
function
disconnect(){pg_close($this->connection);}public
function
query($sql){$this->resultset=@pg_query($this->connection,$sql);if($this->resultset===FALSE){throw
new
DibiDriverException(pg_last_error($this->connection),0,$sql);}return
is_resource($this->resultset);}public
function
affectedRows(){return
pg_affected_rows($this->resultset);}public
function
insertId($sequence){if($sequence===NULL){$has=$this->query("SELECT LASTVAL()");}else{$has=$this->query("SELECT CURRVAL('$sequence')");}if(!$has)return
FALSE;$row=$this->fetch(FALSE);$this->free();return
is_array($row)?$row[0]:FALSE;}public
function
begin(){$this->query('START TRANSACTION');}public
function
commit(){$this->query('COMMIT');}public
function
rollback(){$this->query('ROLLBACK');}public
function
format($value,$type){if($type===dibi::FIELD_TEXT){if($this->escMethod)return"'".pg_escape_string($this->connection,$value)."'";return"'".pg_escape_string($value)."'";}if($type===dibi::IDENTIFIER){$a=strrpos($value,'.');if($a===FALSE)return'"'.str_replace('"','""',$value).'"';return
substr($value,0,$a).'."'.str_replace('"','""',substr($value,$a+1)).'"';}if($type===dibi::FIELD_BOOL)return$value?'TRUE':'FALSE';if($type===dibi::FIELD_DATE)return
date("'Y-m-d'",$value);if($type===dibi::FIELD_DATETIME)return
date("'Y-m-d H:i:s'",$value);throw
new
InvalidArgumentException('Unsupported formatting type.');}public
function
applyLimit(&$sql,$limit,$offset){if($limit>=0)$sql.=' LIMIT '.(int)$limit;if($offset>0)$sql.=' OFFSET '.(int)$offset;}public
function
rowCount(){return
pg_num_rows($this->resultset);}public
function
fetch($type){return
pg_fetch_array($this->resultset,NULL,$type?PGSQL_ASSOC:PGSQL_NUM);}public
function
seek($row){return
pg_result_seek($this->resultset,$row);}public
function
free(){pg_free_result($this->resultset);$this->resultset=NULL;}public
function
getColumnsMeta(){$hasTable=version_compare(PHP_VERSION,'5.2.0','>=');$count=pg_num_fields($this->resultset);$meta=array();for($i=0;$i<$count;$i++){$meta[]=array('name'=>pg_field_name($this->resultset,$i),'table'=>$hasTable?pg_field_table($this->resultset,$i):NULL,'type'=>pg_field_type($this->resultset,$i),'size'=>pg_field_size($this->resultset,$i),'prtlen'=>pg_field_prtlen($this->resultset,$i),);}return$meta;}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiSqliteDriver
extends
NObject
implements
IDibiDriver{private$connection;private$resultset;private$buffered;private$fmtDate,$fmtDateTime;public
function
__construct(){if(!extension_loaded('sqlite')){throw
new
DibiDriverException("PHP extension 'sqlite' is not loaded.");}}public
function
connect(array&$config){DibiConnection::alias($config,'database','file');$this->fmtDate=isset($config['format:date'])?$config['format:date']:'U';$this->fmtDateTime=isset($config['format:datetime'])?$config['format:datetime']:'U';$errorMsg='';if(empty($config['persistent'])){$this->connection=@sqlite_open($config['database'],0666,$errorMsg);}else{$this->connection=@sqlite_popen($config['database'],0666,$errorMsg);}if(!$this->connection){throw
new
DibiDriverException($errorMsg);}$this->buffered=empty($config['unbuffered']);}public
function
disconnect(){sqlite_close($this->connection);}public
function
query($sql){DibiDriverException::tryError();if($this->buffered){$this->resultset=sqlite_query($this->connection,$sql);}else{$this->resultset=sqlite_unbuffered_query($this->connection,$sql);}if(DibiDriverException::catchError($msg)){throw
new
DibiDriverException($msg,sqlite_last_error($this->connection),$sql);}return
is_resource($this->resultset);}public
function
affectedRows(){return
sqlite_changes($this->connection);}public
function
insertId($sequence){return
sqlite_last_insert_rowid($this->connection);}public
function
begin(){$this->query('BEGIN');}public
function
commit(){$this->query('COMMIT');}public
function
rollback(){$this->query('ROLLBACK');}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return"'".sqlite_escape_string($value)."'";if($type===dibi::IDENTIFIER)return'['.str_replace('.','].[',$value).']';if($type===dibi::FIELD_BOOL)return$value?1:0;if($type===dibi::FIELD_DATE)return
date($this->fmtDate,$value);if($type===dibi::FIELD_DATETIME)return
date($this->fmtDateTime,$value);throw
new
InvalidArgumentException('Unsupported formatting type.');}public
function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.$limit.($offset>0?' OFFSET '.(int)$offset:'');}public
function
rowCount(){if(!$this->buffered){throw
new
DibiDriverException('Row count is not available for unbuffered queries.');}return
sqlite_num_rows($this->resultset);}public
function
fetch($type){return
sqlite_fetch_array($this->resultset,$type?SQLITE_ASSOC:SQLITE_NUM);}public
function
seek($row){if(!$this->buffered){throw
new
DibiDriverException('Cannot seek an unbuffered result set.');}return
sqlite_seek($this->resultset,$row);}public
function
free(){$this->resultset=NULL;}public
function
getColumnsMeta(){$count=sqlite_num_fields($this->resultset);$meta=array();for($i=0;$i<$count;$i++){$meta[]=array('name'=>sqlite_field_name($this->resultset,$i),'table'=>NULL,);}return$meta;}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}