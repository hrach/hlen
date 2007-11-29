<?php
 /**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2007 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://php7.org/dibi/
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://php7.org/dibi/license  dibi license
 * @version    0.9 (Revision: 92, Date: 2007/11/22 11:35:23)
 * @link       http://php7.org/dibi/
 * @package    dibi
 */


if(version_compare(PHP_VERSION,'5.1.0','<')){throw
new
Exception('dibi needs PHP 5.1.0 or newer');}if(!class_exists('NObject',FALSE)){abstract
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
BadMethodCallException("Call to method without name");}$cl=$class=get_class($this);do{if(function_exists($nm=$cl.'_prototype_'.$name)){array_unshift($args,$this);return
call_user_func_array($nm,$args);}}while($cl=get_parent_class($cl));throw
new
BadMethodCallException("Call to undefined method $class::$name()");}protected
function&__get($name){if($name===''){throw
new
LogicException("Cannot read an property without name");}$class=get_class($this);$m='get'.$name;if(self::isCallable($class,$m)){$val=$this->$m();return$val;}else{throw
new
LogicException("Cannot read an undeclared property $class::\$$name");}}protected
function
__set($name,$value){if($name===''){throw
new
LogicException('Cannot assign to an property without name');}$class=get_class($this);if(self::isCallable($class,'get'.$name)){$m='set'.$name;if(self::isCallable($class,$m)){$this->$m($value);}else{throw
new
LogicException("Cannot assign to a read-only property $class::\$$name");}}else{throw
new
LogicException("Cannot assign to an undeclared property $class::\$$name");}}protected
function
__isset($name){return$name!==''&&self::isCallable(get_class($this),'get'.$name);}protected
function
__unset($name){$class=get_class($this);throw
new
LogicException("Cannot unset an property $class::\$$name");}private
static
function
isCallable($c,$m){static$cache;if(!isset($cache[$c])){$cache[$c]=array_flip(get_class_methods($c));}return
isset($cache[$c][$m]);}}abstract
class
NClass{final
public
function
__construct(){throw
new
LogicException("Cannot instantiate static class ".get_class($this));}} }if(!class_exists('NException',FALSE)){class
NException
extends
Exception{private$cause;private
static$oldHandler;private
static$handlerClass;public
function
initCause(Exception$cause){if($this->cause===NULL){$this->cause=$cause;}else{throw
new
BadMethodCallException('Cause was already assigned');}}public
function
getCause(){return$this->cause;}public
function
__toString(){return
parent::__toString().($this->cause===NULL?'':"\nCaused by ".$this->cause->__toString());}public
static
function
catchError($class=__CLASS__){self::$oldHandler=set_error_handler(array(__CLASS__,'_errorHandler'),E_ALL);self::$handlerClass=$class;}public
static
function
restore(){if(self::$oldHandler!==NULL){set_error_handler(self::$oldHandler);self::$oldHandler=NULL;}else{restore_error_handler();}}public
static
function
_errorHandler($code,$message,$file,$line,$context){self::restore();if(ini_get('html_errors')){$message=strip_tags($message);}throw
new
self::$handlerClass($message,$code);}} }class
DibiException
extends
NException{}class
DibiDriverException
extends
DibiException{private$sql;public
function
__construct($message=NULL,$code=0,$sql=NULL){parent::__construct($message,(int)$code);$this->sql=$sql;dibi::notify(NULL,'exception',$this);}final
public
function
getSql(){return$this->sql;}public
function
__toString(){return
parent::__toString().($this->sql?"\nSQL: ".$this->sql:'');}}class
DibiConnection
extends
NObject{private$config;private$driver;private$connected=FALSE;public
function
__construct($config){if(is_string($config)){parse_str($config,$config);}if(!isset($config['driver'])){$config['driver']=dibi::$defaultDriver;}$driver=preg_replace('#[^a-z0-9_]#','_',$config['driver']);$class="Dibi".$driver."Driver";if(!class_exists($class)){include_once
__FILE__."/../../drivers/$driver.php";if(!class_exists($class)){throw
new
DibiException("Unable to create instance of dibi driver class '$class'.");}}$this->config=$config;$this->driver=new$class;if(empty($config['lazy'])){$this->connect();}}public
function
__destruct(){$this->disconnect();}final
protected
function
connect(){if(!$this->connected){$this->driver->connect($this->config);$this->connected=TRUE;dibi::notify($this,'connected');}}final
public
function
disconnect(){if($this->connected){$this->driver->disconnect();$this->connected=FALSE;dibi::notify($this,'disconnected');}}final
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
query($args){if(!is_array($args))$args=func_get_args();$this->connect();$trans=new
DibiTranslator($this->driver);if($trans->translate($args)){return$this->nativeQuery($trans->sql);}else{throw
new
DibiException('SQL translate error: '.$trans->sql);}}final
public
function
test($args){if(!is_array($args))$args=func_get_args();$trans=new
DibiTranslator($this->driver);$ok=$trans->translate($args);dibi::dump($trans->sql);return$ok;}final
public
function
nativeQuery($sql){$this->connect();dibi::$numOfQueries++;dibi::$sql=$sql;dibi::$elapsedTime=FALSE;$time=-microtime(TRUE);dibi::notify($this,'beforeQuery',$sql);$res=$this->driver->query($sql)?new
DibiResult(clone$this->driver):TRUE;$time+=microtime(TRUE);dibi::$elapsedTime=$time;dibi::$totalTime+=$time;dibi::notify($this,'afterQuery',$res);return$res;}public
function
affectedRows(){$rows=$this->driver->affectedRows();return$rows<0?FALSE:$rows;}public
function
insertId($sequence=NULL){$id=$this->driver->insertId($sequence);return$id<1?FALSE:$id;}public
function
begin(){$this->connect();$this->driver->begin();dibi::notify($this,'begin');}public
function
commit(){$this->connect();$this->driver->commit();dibi::notify($this,'commit');}public
function
rollback(){$this->connect();$this->driver->rollback();dibi::notify($this,'rollback');}public
function
escape($value){$this->connect();return$this->driver->format($value,dibi::FIELD_TEXT);}public
function
delimite($value){return$this->driver->format($value,dibi::IDENTIFIER);}public
function
applyLimit(&$sql,$limit,$offset){$this->driver->applyLimit($sql,$limit,$offset);}public
function
getDibiReflection(){throw
new
BadMethodCallException(__METHOD__.' is not implemented');}public
function
errorInfo(){throw
new
BadMethodCallException(__METHOD__.' has been deprecated');}}interface
DibiDriverInterface{function
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
fetch();function
free();function
getResource();function
getResultResource();function
getDibiReflection();}class
DibiResult
extends
NObject
implements
IteratorAggregate,Countable{private$driver;private$convert;private$meta;private$fetched=FALSE;private
static$types=array(dibi::FIELD_TEXT=>'string',dibi::FIELD_BINARY=>'string',dibi::FIELD_BOOL=>'bool',dibi::FIELD_INTEGER=>'int',dibi::FIELD_FLOAT=>'float',dibi::FIELD_COUNTER=>'int',);public
function
__construct($driver){$this->driver=$driver;}public
function
__destruct(){@$this->free();}final
public
function
getResource(){return$this->getDriver()->getResultResource();}final
public
function
seek($row){if($row!==0||$this->fetched){return(bool)$this->getDriver()->seek($row);}}final
public
function
rowCount(){return$this->getDriver()->rowCount();}final
public
function
free(){if($this->driver!==NULL){$this->driver->free();$this->driver=NULL;}}final
public
function
fetch(){$row=$this->getDriver()->fetch();if(!is_array($row))return
FALSE;$this->fetched=TRUE;if($t=$this->convert){foreach($row
as$key=>$value){if(isset($t[$key])){$row[$key]=$this->convert($value,$t[$key]);}}}return$row;}final
function
fetchSingle(){$row=$this->getDriver()->fetch();if(!is_array($row))return
FALSE;$this->fetched=TRUE;if($t=$this->convert){$value=reset($row);$key=key($row);return
isset($t[$key])?$this->convert($value,$t[$key]):$value;}return
reset($row);}final
function
fetchAll(){$this->seek(0);$row=$this->fetch();if(!$row)return
array();$data=array();if(count($row)===1){$key=key($row);do{$data[]=$row[$key];}while($row=$this->fetch());}else{do{$data[]=$row;}while($row=$this->fetch());}return$data;}final
function
fetchAssoc($assoc){$this->seek(0);$row=$this->fetch();if(!$row)return
array();$data=NULL;$assoc=explode(',',$assoc);foreach($assoc
as$as){if($as!=='#'&&$as!=='='&&!array_key_exists($as,$row)){throw
new
InvalidArgumentException("Unknown column '$as' in associative descriptor");}}if(count($assoc)===1){$as=$assoc[0];do{$data[$row[$as]]=$row;}while($row=$this->fetch());return$data;}$last=count($assoc)-1;if($assoc[$last]==='=')unset($assoc[$last]);do{$x=&$data;foreach($assoc
as$i=>$as){if($as==='#'){$x=&$x[];}elseif($as==='='){if($x===NULL){$x=$row;$x=&$x[$assoc[$i+1]];$x=NULL;}else{$x=&$x[$assoc[$i+1]];}}else{$x=&$x[$row[$as]];}}if($x===NULL)$x=$row;}while($row=$this->fetch());unset($x);return$data;}final
function
fetchPairs($key=NULL,$value=NULL){$this->seek(0);$row=$this->fetch();if(!$row)return
array();$data=array();if($value===NULL){if($key!==NULL){throw
new
InvalidArgumentException("Either none or both fields must be specified");}if(count($row)<2){throw
new
LoginException("Result must have at least two columns");}$tmp=array_keys($row);$key=$tmp[0];$value=$tmp[1];}else{if(!array_key_exists($value,$row)){throw
new
InvalidArgumentException("Unknown value column '$value'");}if($key===NULL){do{$data[]=$row[$value];}while($row=$this->fetch());return$data;}if(!array_key_exists($key,$row)){throw
new
InvalidArgumentException("Unknown key column '$key'");}}do{$data[$row[$key]]=$row[$value];}while($row=$this->fetch());return$data;}final
public
function
setType($field,$type=NULL){if($field===TRUE){$this->buildMeta();}elseif(is_array($field)){$this->convert=$field;}else{$this->convert[$field]=$type;}}final
public
function
getType($field){return
isset($this->convert[$field])?$this->convert[$field]:NULL;}final
public
function
convert($value,$type){if($value===NULL||$value===FALSE){return$value;}if(isset(self::$types[$type])){settype($value,self::$types[$type]);return$value;}if($type===dibi::FIELD_DATE||$type===dibi::FIELD_DATETIME){return
strtotime($value);}return$value;}final
public
function
getFields(){$this->buildMeta();return
array_keys($this->meta);}final
public
function
getMetaData($field){$this->buildMeta();return
isset($this->meta[$field])?$this->meta[$field]:FALSE;}final
protected
function
buildMeta(){if($this->meta===NULL){$this->meta=$this->getDriver()->buildMeta();foreach($this->meta
as$name=>$info){$this->convert[$name]=$info['type'];}}}final
public
function
dump(){echo"\n<table class=\"dump\">\n<thead>\n\t<tr>\n\t\t<th>#row</th>\n";foreach($this->getFields()as$field){echo"\t\t<th>".htmlSpecialChars($field)."</th>\n";}echo"\t</tr>\n</thead>\n<tbody>\n";foreach($this
as$row=>$fields){echo"\t<tr>\n\t\t<th>",$row,"</th>\n";foreach($fields
as$field){echo"\t\t<td>",htmlSpecialChars($field),"</td>\n";}echo"\t</tr>\n";}echo"</tbody>\n</table>\n";}final
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
DibiException('Resultset was released from memory');}return$this->driver;}}final
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
valid(){return
is_array($this->row)&&($this->limit<0||$this->pointer<$this->limit);}}final
class
DibiTranslator
extends
NObject{public$sql;public$mask;private$driver;private$modifier;private$hasError;private$comment;private$ifLevel;private$ifLevelStart;public
function
__construct(DibiDriverInterface$driver){$this->driver=$driver;}public
function
translate(array$args){$this->hasError=FALSE;$commandIns=NULL;$lastArr=NULL;$mod=&$this->modifier;$mod=FALSE;$this->ifLevel=$this->ifLevelStart=0;$comment=&$this->comment;$comment=FALSE;$sql=$mask=array();$i=0;foreach($args
as$arg){$i++;if($mod==='if'){$mod=FALSE;$this->ifLevel++;if(!$comment&&!$arg){$sql[]="\0";$this->ifLevelStart=$this->ifLevel;$comment=TRUE;}continue;}if(is_string($arg)&&(!$mod||$mod==='sql')){$mod=FALSE;$sql[]=$this->formatValue($arg,'sql');continue;}if(!$mod&&is_array($arg)&&is_string(key($arg))){if($commandIns===NULL){$commandIns=strtoupper(substr(ltrim($args[0]),0,6));$commandIns=$commandIns==='INSERT'||$commandIns==='REPLAC';$mod=$commandIns?'v':'a';}else{$mod=$commandIns?'l':'a';if($lastArr===$i-1)$sql[]=',';}$lastArr=$i;}if(!$comment){$sql[]=$this->formatValue($arg,$mod);}$mod=FALSE;}if($comment)$sql[]="\0";$this->sql=implode(' ',$sql);$this->sql=preg_replace('#\x00.*?\x00#s','',$this->sql);return!$this->hasError;}private
function
formatValue($value,$modifier){if(is_array($value)){$vx=$kx=array();switch($modifier){case'a':foreach($value
as$k=>$v){$pair=explode('%',$k,2);$vx[]=$this->delimite($pair[0]).'='.$this->formatValue($v,isset($pair[1])?$pair[1]:FALSE);}return
implode(', ',$vx);case'l':$kx=NULL;case'v':foreach($value
as$k=>$v){$pair=explode('%',$k,2);if($kx!==NULL){$kx[]=$this->delimite($pair[0]);}$vx[]=$this->formatValue($v,isset($pair[1])?$pair[1]:FALSE);}if($kx===NULL){return'('.implode(', ',$vx).')';}else{return'('.implode(', ',$kx).') VALUES ('.implode(', ',$vx).')';}default:foreach($value
as$v){$vx[]=$this->formatValue($v,$modifier);}return
implode(', ',$vx);}}if($modifier){if($value===NULL){return'NULL';}if($value
instanceof
DibiVariableInterface){return$value->toSql($this->driver,$modifier);}if(!is_scalar($value)){$this->hasError=TRUE;return'**Unexpected type '.gettype($value).'**';}switch($modifier){case's':return$this->driver->format($value,dibi::FIELD_TEXT);case'sn':return$value==''?'NULL':$this->driver->format($value,dibi::FIELD_TEXT);case'b':return$this->driver->format($value,dibi::FIELD_BOOL);case'i':case'u':if(is_string($value)&&preg_match('#[+-]?\d+(e\d+)?$#A',$value)){return$value;}return(string)(int)($value+0);case'f':if(is_numeric($value)&&(!is_string($value)||strpos($value,'x')===FALSE)){return$value;}return(string)($value+0);case'd':return$this->driver->format(is_string($value)?strtotime($value):$value,dibi::FIELD_DATE);case't':return$this->driver->format(is_string($value)?strtotime($value):$value,dibi::FIELD_DATETIME);case'n':return$this->delimite($value);case'sql':case'p':$value=(string)$value;$toSkip=strcspn($value,'`[\'"%');if(strlen($value)===$toSkip){return$value;}return
substr($value,0,$toSkip).preg_replace_callback('/(?=`|\[|\'|"|%)(?:`(.+?)`|\[(.+?)\]|(\')((?:\'\'|[^\'])*)\'|(")((?:""|[^"])*)"|%(else|end)|%([a-zA-Z]{1,3})$|(\'|"))/s',array($this,'cb'),substr($value,$toSkip));case'a':case'v':$this->hasError=TRUE;return'**Unexpected type '.gettype($value).'**';case'if':$this->hasError=TRUE;return"**The %$modifier is not allowed here**";default:$this->hasError=TRUE;return"**Unknown modifier %$modifier**";}}if(is_string($value))return$this->driver->format($value,dibi::FIELD_TEXT);if(is_int($value)||is_float($value))return(string)$value;if(is_bool($value))return$this->driver->format($value,dibi::FIELD_BOOL);if($value===NULL)return'NULL';if($value
instanceof
DibiVariableInterface)return$value->toSql($this->driver,NULL);$this->hasError=TRUE;return'**Unexpected '.gettype($value).'**';}private
function
cb($matches){if(!empty($matches[7])){if(!$this->ifLevel){$this->hasError=TRUE;return"**Unexpected condition $matches[7]**";}if($matches[7]==='end'){$this->ifLevel--;if($this->ifLevelStart===$this->ifLevel+1){$this->ifLevelStart=0;$this->comment=FALSE;return"\0";}return'';}if($this->ifLevelStart===$this->ifLevel){$this->ifLevelStart=0;$this->comment=FALSE;return"\0";}elseif(!$this->comment){$this->ifLevelStart=$this->ifLevel;$this->comment=TRUE;return"\0";}}if(!empty($matches[8])){$this->modifier=$matches[8];return'';}if($this->comment)return'';if($matches[1])return$this->delimite($matches[1]);if($matches[2])return$this->delimite($matches[2]);if($matches[3])return$this->driver->format(str_replace("''","'",$matches[4]),dibi::FIELD_TEXT);if($matches[5])return$this->driver->format(str_replace('""','"',$matches[6]),dibi::FIELD_TEXT);if($matches[9]){$this->hasError=TRUE;return'**Alone quote**';}die('this should be never executed');}private
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
write($message){$handle=fopen($this->file,'a');if(!$handle)return;flock($handle,LOCK_EX);fwrite($handle,$message);fclose($handle);}}interface
DibiVariableInterface{public
function
toSql(DibiDriverInterface$driver,$modifier);}class
DibiVariable
extends
NObject
implements
DibiVariableInterface{public$value;public$type;public
function
__construct($value,$type){$this->value=$value;$this->type=$type;}public
function
toSql(DibiDriverInterface$driver,$modifier){return$driver->format($this->value,$this->type);}} 
class
dibi
extends
NClass{const
FIELD_TEXT='s',FIELD_BINARY='S',FIELD_BOOL='b',FIELD_INTEGER='i',FIELD_FLOAT='f',FIELD_DATE='d',FIELD_DATETIME='t',FIELD_UNKNOWN='?',FIELD_COUNTER='C',IDENTIFIER='I',VERSION='0.9 (Revision: 92, Date: 2007/11/22 11:35:23)';private
static$registry=array();private
static$connection;private
static$substs=array();private
static$handlers=array();public
static$sql;public
static$elapsedTime;public
static$totalTime;public
static$numOfQueries=0;public
static$defaultDriver='mysql';public
static
function
connect($config=array(),$name=0){if(is_array($config)){$config['name']=$name;}else{$config.='&name='.urlencode($name);}return
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
DibiException('Dibi is not connected to database');}return
self::$connection;}if(!isset(self::$registry[$name])){throw
new
DibiException("There is no connection named '$name'.");}return
self::$registry[$name];}public
static
function
activate($name){self::$connection=self::getConnection($name);}public
static
function
query($args){if(!is_array($args))$args=func_get_args();return
self::getConnection()->query($args);}public
static
function
nativeQuery($sql){return
self::getConnection()->nativeQuery($sql);}public
static
function
test($args){if(!is_array($args))$args=func_get_args();return
self::getConnection()->test($args);}public
static
function
fetch($args){if(!is_array($args))$args=func_get_args();return
self::getConnection()->query($args)->fetch();}public
static
function
fetchAll($args){if(!is_array($args))$args=func_get_args();return
self::getConnection()->query($args)->fetchAll();}public
static
function
fetchSingle($args){if(!is_array($args))$args=func_get_args();return
self::getConnection()->query($args)->fetchSingle();}public
static
function
insertId($sequence=NULL){return
self::getConnection()->insertId($sequence);}public
static
function
affectedRows(){return
self::getConnection()->affectedRows();}public
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
__callStatic($name,$args){return
call_user_func_array(array(self::getConnection(),$name),$args);}public
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
InvalidArgumentException("Invalid callback");}self::$handlers[]=$callback;}public
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
DibiResult){$sql->dump();}else{if($sql===NULL)$sql=self::$sql;static$keywords2='ALL|DISTINCT|AS|ON|INTO|AND|OR|AS';static$keywords1='SELECT|UPDATE|INSERT|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN';$sql=preg_replace("#\\b(?:$keywords1)\\b#","\n\$0",$sql);$sql=trim($sql);$sql=preg_replace('# {2,}#',' ',$sql);$sql=wordwrap($sql,100);$sql=htmlSpecialChars($sql);$sql=preg_replace("#\n{2,}#","\n",$sql);$sql=preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|\\b($keywords1)\\b|\\b($keywords2)\\b#",array('dibi','highlightCallback'),$sql);echo'<pre class="dump">',$sql,"</pre>\n";}if($return){return
ob_get_clean();}else{ob_end_flush();}}private
static
function
highlightCallback($matches){if(!empty($matches[1]))return'<em style="color:gray">'.$matches[1].'</em>';if(!empty($matches[2]))return'<strong style="color:red">'.$matches[2].'</strong>';if(!empty($matches[3]))return'<strong style="color:blue">'.$matches[3].'</strong>';if(!empty($matches[4]))return'<strong style="color:green">'.$matches[4].'</strong>';}}class
DibiMsSqlDriver
extends
NObject
implements
DibiDriverInterface{private$connection;private$resultset;public
function
connect(array&$config){DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');DibiConnection::alias($config,'host');if(!extension_loaded('mssql')){throw
new
DibiException("PHP extension 'mssql' is not loaded");}if(empty($config['persistent'])){$this->connection=@mssql_connect($config['host'],$config['username'],$config['password'],TRUE);}else{$this->connection=@mssql_pconnect($config['host'],$config['username'],$config['password']);}if(!is_resource($this->connection)){throw
new
DibiDriverException("Can't connect to DB");}if(isset($config['database'])&&!@mssql_select_db($config['database'],$this->connection)){throw
new
DibiDriverException("Can't select DB '$config[database]'");}}public
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
BadMethodCallException(__METHOD__.' is not implemented');}public
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
InvalidArgumentException('Unsupported formatting type');}public
function
applyLimit(&$sql,$limit,$offset){if($limit>=0){$sql='SELECT TOP '.(int)$limit.' * FROM ('.$sql.')';}if($offset){throw
new
InvalidArgumentException('Offset is not implemented');}}public
function
rowCount(){return
mssql_num_rows($this->resultset);}public
function
fetch(){return
mssql_fetch_assoc($this->resultset);}public
function
seek($row){return
mssql_data_seek($this->resultset,$row);}public
function
free(){mssql_free_result($this->resultset);$this->resultset=NULL;}public
function
buildMeta(){static$types=array('CHAR'=>dibi::FIELD_TEXT,'COUNTER'=>dibi::FIELD_COUNTER,'VARCHAR'=>dibi::FIELD_TEXT,'LONGCHAR'=>dibi::FIELD_TEXT,'INTEGER'=>dibi::FIELD_INTEGER,'DATETIME'=>dibi::FIELD_DATETIME,'CURRENCY'=>dibi::FIELD_FLOAT,'BIT'=>dibi::FIELD_BOOL,'LONGBINARY'=>dibi::FIELD_BINARY,'SMALLINT'=>dibi::FIELD_INTEGER,'BYTE'=>dibi::FIELD_INTEGER,'BIGINT'=>dibi::FIELD_INTEGER,'INT'=>dibi::FIELD_INTEGER,'TINYINT'=>dibi::FIELD_INTEGER,'REAL'=>dibi::FIELD_FLOAT,'DOUBLE'=>dibi::FIELD_FLOAT,'DECIMAL'=>dibi::FIELD_FLOAT,'NUMERIC'=>dibi::FIELD_FLOAT,'MONEY'=>dibi::FIELD_FLOAT,'SMALLMONEY'=>dibi::FIELD_FLOAT,'FLOAT'=>dibi::FIELD_FLOAT,'YESNO'=>dibi::FIELD_BOOL,);$count=mssql_num_fields($this->resultset);$meta=array();for($index=0;$index<$count;$index++){$tmp=mssql_fetch_field($this->resultset,$index);$type=strtoupper($tmp->type);$info['native']=$tmp->type;$info['type']=isset($types[$type])?$types[$type]:dibi::FIELD_UNKNOWN;$info['length']=$tmp->max_length;$info['table']=$tmp->column_source;$meta[$tmp->name]=$info;}return$meta;}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiMySqlDriver
extends
NObject
implements
DibiDriverInterface{private$connection;private$resultset;private$buffered;public
function
connect(array&$config){if(!extension_loaded('mysql')){throw
new
DibiException("PHP extension 'mysql' is not loaded");}DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');DibiConnection::alias($config,'options');if(!isset($config['username']))$config['username']=ini_get('mysql.default_user');if(!isset($config['password']))$config['password']=ini_get('mysql.default_password');if(!isset($config['host'])){$host=ini_get('mysql.default_host');if($host){$config['host']=$host;$config['port']=ini_get('mysql.default_port');}else{if(!isset($config['socket']))$config['socket']=ini_get('mysql.default_socket');$config['host']=NULL;}}if(empty($config['socket'])){$host=$config['host'].(empty($config['port'])?'':':'.$config['port']);}else{$host=':'.$config['socket'];}if(empty($config['persistent'])){$this->connection=@mysql_connect($host,$config['username'],$config['password'],TRUE,$config['options']);}else{$this->connection=@mysql_pconnect($host,$config['username'],$config['password'],$config['options']);}if(!is_resource($this->connection)){throw
new
DibiDriverException(mysql_error(),mysql_errno());}if(isset($config['charset'])){@mysql_query("SET NAMES '".$config['charset']."'",$this->connection);}if(isset($config['database'])&&!@mysql_select_db($config['database'],$this->connection)){throw
new
DibiDriverException(mysql_error($this->connection),mysql_errno($this->connection));}$this->buffered=empty($config['unbuffered']);}public
function
disconnect(){mysql_close($this->connection);}public
function
query($sql){if($this->buffered){$this->resultset=@mysql_query($sql,$this->connection);}else{$this->resultset=@mysql_unbuffered_query($sql,$this->connection);}if($errno=mysql_errno($this->connection)){throw
new
DibiDriverException(mysql_error($this->connection),$errno,$sql);}return
is_resource($this->resultset);}public
function
affectedRows(){return
mysql_affected_rows($this->connection);}public
function
insertId($sequence){return
mysql_insert_id($this->connection);}public
function
begin(){$this->query('BEGIN');}public
function
commit(){$this->query('COMMIT');}public
function
rollback(){$this->query('ROLLBACK');}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return"'".mysql_real_escape_string($value,$this->connection)."'";if($type===dibi::IDENTIFIER)return'`'.str_replace('.','`.`',$value).'`';if($type===dibi::FIELD_BOOL)return$value?1:0;if($type===dibi::FIELD_DATE)return
date("'Y-m-d'",$value);if($type===dibi::FIELD_DATETIME)return
date("'Y-m-d H:i:s'",$value);throw
new
InvalidArgumentException('Unsupported formatting type');}public
function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.($limit<0?'18446744073709551615':(int)$limit).($offset>0?' OFFSET '.(int)$offset:'');}public
function
rowCount(){if(!$this->buffered){throw
new
DibiDriverException('Row count is not available for unbuffered queries');}return
mysql_num_rows($this->resultset);}public
function
fetch(){return
mysql_fetch_assoc($this->resultset);}public
function
seek($row){if(!$this->buffered){throw
new
DibiDriverException('Cannot seek an unbuffered result set');}return
mysql_data_seek($this->resultset,$row);}public
function
free(){mysql_free_result($this->resultset);$this->resultset=NULL;}public
function
buildMeta(){static$types=array('ENUM'=>dibi::FIELD_TEXT,'SET'=>dibi::FIELD_TEXT,'CHAR'=>dibi::FIELD_TEXT,'VARCHAR'=>dibi::FIELD_TEXT,'STRING'=>dibi::FIELD_TEXT,'TINYTEXT'=>dibi::FIELD_TEXT,'TEXT'=>dibi::FIELD_TEXT,'MEDIUMTEXT'=>dibi::FIELD_TEXT,'LONGTEXT'=>dibi::FIELD_TEXT,'BINARY'=>dibi::FIELD_BINARY,'VARBINARY'=>dibi::FIELD_BINARY,'TINYBLOB'=>dibi::FIELD_BINARY,'BLOB'=>dibi::FIELD_BINARY,'MEDIUMBLOB'=>dibi::FIELD_BINARY,'LONGBLOB'=>dibi::FIELD_BINARY,'DATE'=>dibi::FIELD_DATE,'DATETIME'=>dibi::FIELD_DATETIME,'TIMESTAMP'=>dibi::FIELD_DATETIME,'TIME'=>dibi::FIELD_DATETIME,'BIT'=>dibi::FIELD_BOOL,'YEAR'=>dibi::FIELD_INTEGER,'TINYINT'=>dibi::FIELD_INTEGER,'SMALLINT'=>dibi::FIELD_INTEGER,'MEDIUMINT'=>dibi::FIELD_INTEGER,'INT'=>dibi::FIELD_INTEGER,'INTEGER'=>dibi::FIELD_INTEGER,'BIGINT'=>dibi::FIELD_INTEGER,'FLOAT'=>dibi::FIELD_FLOAT,'DOUBLE'=>dibi::FIELD_FLOAT,'REAL'=>dibi::FIELD_FLOAT,'DECIMAL'=>dibi::FIELD_FLOAT,'NUMERIC'=>dibi::FIELD_FLOAT,);$count=mysql_num_fields($this->resultset);$meta=array();for($index=0;$index<$count;$index++){$info['native']=$native=strtoupper(mysql_field_type($this->resultset,$index));$info['flags']=explode(' ',mysql_field_flags($this->resultset,$index));$info['length']=mysql_field_len($this->resultset,$index);$info['table']=mysql_field_table($this->resultset,$index);if(in_array('auto_increment',$info['flags'])){$info['type']=dibi::FIELD_COUNTER;}else{$info['type']=isset($types[$native])?$types[$native]:dibi::FIELD_UNKNOWN;}$name=mysql_field_name($this->resultset,$index);$meta[$name]=$info;}return$meta;}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiMySqliDriver
extends
NObject
implements
DibiDriverInterface{private$connection;private$resultset;private$buffered;public
function
connect(array&$config){DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');DibiConnection::alias($config,'options');DibiConnection::alias($config,'database');if(!isset($config['username']))$config['username']=ini_get('mysqli.default_user');if(!isset($config['password']))$config['password']=ini_get('mysqli.default_password');if(!isset($config['socket']))$config['socket']=ini_get('mysqli.default_socket');if(!isset($config['host'])){$config['host']=ini_get('mysqli.default_host');if(!isset($config['port']))ini_get('mysqli.default_port');if(!isset($config['host']))$config['host']='localhost';}if(!extension_loaded('mysqli')){throw
new
DibiException("PHP extension 'mysqli' is not loaded");}$this->connection=mysqli_init();@mysqli_real_connect($this->connection,$config['host'],$config['username'],$config['password'],$config['database'],$config['port'],$config['socket'],$config['options']);if($errno=mysqli_connect_errno()){throw
new
DibiDriverException(mysqli_connect_error(),$errno);}if(isset($config['charset'])){mysqli_query($this->connection,"SET NAMES '".$config['charset']."'");}$this->buffered=empty($config['unbuffered']);}public
function
disconnect(){mysqli_close($this->connection);}public
function
query($sql){$this->resultset=@mysqli_query($this->connection,$sql,$this->buffered?MYSQLI_STORE_RESULT:MYSQLI_USE_RESULT);if($errno=mysqli_errno($this->connection)){throw
new
DibiDriverException(mysqli_error($this->connection),$errno,$sql);}return
is_object($this->resultset);}public
function
affectedRows(){return
mysqli_affected_rows($this->connection);}public
function
insertId($sequence){return
mysqli_insert_id($this->connection);}public
function
begin(){if(!mysqli_autocommit($this->connection,FALSE)){throw
new
DibiDriverException(mysqli_error($this->connection),mysqli_errno($this->connection));}}public
function
commit(){if(!mysqli_commit($this->connection)){throw
new
DibiDriverException(mysqli_error($this->connection),mysqli_errno($this->connection));}mysqli_autocommit($this->connection,TRUE);}public
function
rollback(){if(!mysqli_rollback($this->connection)){throw
new
DibiDriverException(mysqli_error($this->connection),mysqli_errno($this->connection));}mysqli_autocommit($this->connection,TRUE);}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return"'".mysqli_real_escape_string($this->connection,$value)."'";if($type===dibi::IDENTIFIER)return'`'.str_replace('.','`.`',$value).'`';if($type===dibi::FIELD_BOOL)return$value?1:0;if($type===dibi::FIELD_DATE)return
date("'Y-m-d'",$value);if($type===dibi::FIELD_DATETIME)return
date("'Y-m-d H:i:s'",$value);throw
new
InvalidArgumentException('Unsupported formatting type');}public
function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.($limit<0?'18446744073709551615':(int)$limit).($offset>0?' OFFSET '.(int)$offset:'');}public
function
rowCount(){if(!$this->buffered){throw
new
DibiDriverException('Row count is not available for unbuffered queries');}return
mysqli_num_rows($this->resultset);}public
function
fetch(){return
mysqli_fetch_assoc($this->resultset);}public
function
seek($row){if(!$this->buffered){throw
new
DibiDriverException('Cannot seek an unbuffered result set');}return
mysqli_data_seek($this->resultset,$row);}public
function
free(){mysqli_free_result($this->resultset);$this->resultset=NULL;}public
function
buildMeta(){static$types=array(MYSQLI_TYPE_FLOAT=>dibi::FIELD_FLOAT,MYSQLI_TYPE_DOUBLE=>dibi::FIELD_FLOAT,MYSQLI_TYPE_DECIMAL=>dibi::FIELD_FLOAT,MYSQLI_TYPE_TINY=>dibi::FIELD_INTEGER,MYSQLI_TYPE_SHORT=>dibi::FIELD_INTEGER,MYSQLI_TYPE_LONG=>dibi::FIELD_INTEGER,MYSQLI_TYPE_LONGLONG=>dibi::FIELD_INTEGER,MYSQLI_TYPE_INT24=>dibi::FIELD_INTEGER,MYSQLI_TYPE_YEAR=>dibi::FIELD_INTEGER,MYSQLI_TYPE_GEOMETRY=>dibi::FIELD_INTEGER,MYSQLI_TYPE_DATE=>dibi::FIELD_DATE,MYSQLI_TYPE_NEWDATE=>dibi::FIELD_DATE,MYSQLI_TYPE_TIMESTAMP=>dibi::FIELD_DATETIME,MYSQLI_TYPE_TIME=>dibi::FIELD_DATETIME,MYSQLI_TYPE_DATETIME=>dibi::FIELD_DATETIME,MYSQLI_TYPE_ENUM=>dibi::FIELD_TEXT,MYSQLI_TYPE_SET=>dibi::FIELD_TEXT,MYSQLI_TYPE_STRING=>dibi::FIELD_TEXT,MYSQLI_TYPE_VAR_STRING=>dibi::FIELD_TEXT,MYSQLI_TYPE_TINY_BLOB=>dibi::FIELD_BINARY,MYSQLI_TYPE_MEDIUM_BLOB=>dibi::FIELD_BINARY,MYSQLI_TYPE_LONG_BLOB=>dibi::FIELD_BINARY,MYSQLI_TYPE_BLOB=>dibi::FIELD_BINARY,);$count=mysqli_num_fields($this->resultset);$meta=array();for($index=0;$index<$count;$index++){$info=(array)mysqli_fetch_field_direct($this->resultset,$index);$native=$info['native']=$info['type'];if($info['flags']&MYSQLI_AUTO_INCREMENT_FLAG){$info['type']=dibi::FIELD_COUNTER;}else{$info['type']=isset($types[$native])?$types[$native]:dibi::FIELD_UNKNOWN;}$meta[$info['name']]=$info;}return$meta;}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiOdbcDriver
extends
NObject
implements
DibiDriverInterface{private$connection;private$resultset;private$row=0;public
function
connect(array&$config){DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');if(!isset($config['username']))$config['username']=ini_get('odbc.default_user');if(!isset($config['password']))$config['password']=ini_get('odbc.default_pw');if(!isset($config['dsn']))$config['dsn']=ini_get('odbc.default_db');if(!extension_loaded('odbc')){throw
new
DibiException("PHP extension 'odbc' is not loaded");}if(empty($config['persistent'])){$this->connection=@odbc_connect($config['dsn'],$config['username'],$config['password']);}else{$this->connection=@odbc_pconnect($config['dsn'],$config['username'],$config['password']);}if(!is_resource($this->connection)){throw
new
DibiDriverException(odbc_errormsg().' '.odbc_error());}}public
function
disconnect(){odbc_close($this->connection);}public
function
query($sql){$this->resultset=@odbc_exec($this->connection,$sql);if($this->resultset===FALSE){throw
new
DibiDriverException(odbc_errormsg($this->connection).' '.odbc_error($this->connection),0,$sql);}return
is_resource($this->resultset);}public
function
affectedRows(){return
odbc_num_rows($this->resultset);}public
function
insertId($sequence){throw
new
BadMethodCallException(__METHOD__.' is not implemented');}public
function
begin(){if(!odbc_autocommit($this->connection,FALSE)){throw
new
DibiDriverException(odbc_errormsg($this->connection).' '.odbc_error($this->connection));}}public
function
commit(){if(!odbc_commit($this->connection)){throw
new
DibiDriverException(odbc_errormsg($this->connection).' '.odbc_error($this->connection));}odbc_autocommit($this->connection,TRUE);}public
function
rollback(){if(!odbc_rollback($this->connection)){throw
new
DibiDriverException(odbc_errormsg($this->connection).' '.odbc_error($this->connection));}odbc_autocommit($this->connection,TRUE);}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return"'".str_replace("'","''",$value)."'";if($type===dibi::IDENTIFIER)return'['.str_replace('.','].[',$value).']';if($type===dibi::FIELD_BOOL)return$value?-1:0;if($type===dibi::FIELD_DATE)return
date("#m/d/Y#",$value);if($type===dibi::FIELD_DATETIME)return
date("#m/d/Y H:i:s#",$value);throw
new
InvalidArgumentException('Unsupported formatting type');}public
function
applyLimit(&$sql,$limit,$offset){if($limit>=0){$sql='SELECT TOP '.(int)$limit.' * FROM ('.$sql.')';}if($offset)throw
new
InvalidArgumentException('Offset is not implemented in driver odbc');}public
function
rowCount(){return
odbc_num_rows($this->resultset);}public
function
fetch(){return
odbc_fetch_array($this->resultset,++$this->row);}public
function
seek($row){$this->row=$row;return
TRUE;}public
function
free(){odbc_free_result($this->resultset);$this->resultset=NULL;}public
function
buildMeta(){static$types=array('CHAR'=>dibi::FIELD_TEXT,'COUNTER'=>dibi::FIELD_COUNTER,'VARCHAR'=>dibi::FIELD_TEXT,'LONGCHAR'=>dibi::FIELD_TEXT,'INTEGER'=>dibi::FIELD_INTEGER,'DATETIME'=>dibi::FIELD_DATETIME,'CURRENCY'=>dibi::FIELD_FLOAT,'BIT'=>dibi::FIELD_BOOL,'LONGBINARY'=>dibi::FIELD_BINARY,'SMALLINT'=>dibi::FIELD_INTEGER,'BYTE'=>dibi::FIELD_INTEGER,'BIGINT'=>dibi::FIELD_INTEGER,'INT'=>dibi::FIELD_INTEGER,'TINYINT'=>dibi::FIELD_INTEGER,'REAL'=>dibi::FIELD_FLOAT,'DOUBLE'=>dibi::FIELD_FLOAT,'DECIMAL'=>dibi::FIELD_FLOAT,'NUMERIC'=>dibi::FIELD_FLOAT,'MONEY'=>dibi::FIELD_FLOAT,'SMALLMONEY'=>dibi::FIELD_FLOAT,'FLOAT'=>dibi::FIELD_FLOAT,'YESNO'=>dibi::FIELD_BOOL,);$count=odbc_num_fields($this->resultset);$meta=array();for($index=1;$index<=$count;$index++){$native=strtoupper(odbc_field_type($this->resultset,$index));$name=odbc_field_name($this->resultset,$index);$meta[$name]=array('type'=>isset($types[$native])?$types[$native]:dibi::FIELD_UNKNOWN,'native'=>$native,'length'=>odbc_field_len($this->resultset,$index),'scale'=>odbc_field_scale($this->resultset,$index),'precision'=>odbc_field_precision($this->resultset,$index),);}return$meta;}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiOracleDriver
extends
NObject
implements
DibiDriverInterface{private$connection;private$resultset;private$autocommit=TRUE;public
function
connect(array&$config){DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');DibiConnection::alias($config,'database','db');DibiConnection::alias($config,'charset');if(!extension_loaded('oci8')){throw
new
DibiException("PHP extension 'oci8' is not loaded");}$this->connection=@oci_new_connect($config['username'],$config['password'],$config['database'],$config['charset']);if(!$this->connection){$err=oci_error();throw
new
DibiDriverException($err['message'],$err['code']);}}public
function
disconnect(){oci_close($this->connection);}public
function
query($sql){$this->resultset=oci_parse($this->connection,$sql);if($this->resultset){oci_execute($this->resultset,$this->autocommit?OCI_COMMIT_ON_SUCCESS:OCI_DEFAULT);$err=oci_error($this->resultset);if($err){throw
new
DibiDriverException($err['message'],$err['code'],$sql);}}else{$err=oci_error($this->connection);throw
new
DibiDriverException($err['message'],$err['code'],$sql);}return
is_resource($this->resultset);}public
function
affectedRows(){throw
new
BadMethodCallException(__METHOD__.' is not implemented');}public
function
insertId($sequence){throw
new
BadMethodCallException(__METHOD__.' is not implemented');}public
function
begin(){$this->autocommit=FALSE;}public
function
commit(){if(!oci_commit($this->connection)){$err=oci_error($this->connection);throw
new
DibiDriverException($err['message'],$err['code']);}$this->autocommit=TRUE;}public
function
rollback(){if(!oci_rollback($this->connection)){$err=oci_error($this->connection);throw
new
DibiDriverException($err['message'],$err['code']);}$this->autocommit=TRUE;}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return"'".str_replace("'","''",$value)."'";if($type===dibi::IDENTIFIER)return'['.str_replace('.','].[',$value).']';if($type===dibi::FIELD_BOOL)return$value?1:0;if($type===dibi::FIELD_DATE)return
date("U",$value);if($type===dibi::FIELD_DATETIME)return
date("U",$value);throw
new
InvalidArgumentException('Unsupported formatting type');}public
function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.$limit.($offset>0?' OFFSET '.(int)$offset:'');}public
function
rowCount(){return
oci_num_rows($this->resultset);}public
function
fetch(){return
oci_fetch_assoc($this->resultset);}public
function
seek($row){throw
new
BadMethodCallException(__METHOD__.' is not implemented');}public
function
free(){oci_free_statement($this->resultset);$this->resultset=NULL;}public
function
buildMeta(){$count=oci_num_fields($this->resultset);$meta=array();for($index=0;$index<$count;$index++){$name=oci_field_name($this->resultset,$index+1);$meta[$name]=array('type'=>dibi::FIELD_UNKNOWN);}return$meta;}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiPdoDriver
extends
NObject
implements
DibiDriverInterface{private$connection;private$resultset;public
function
connect(array&$config){DibiConnection::alias($config,'username','user');DibiConnection::alias($config,'password','pass');DibiConnection::alias($config,'dsn');DibiConnection::alias($config,'options');if(!extension_loaded('pdo')){throw
new
DibiException("PHP extension 'pdo' is not loaded");}try{$this->connection=new
PDO($config['dsn'],$config['username'],$config['password'],$config['options']);}catch(PDOException$e){throw
new
DibiDriverException($e->getMessage(),$e->getCode());}if(!$this->connection){throw
new
DibiDriverException('Connecting error');}$this->connection->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);}public
function
disconnect(){$this->connection=NULL;}public
function
query($sql){try{$this->resultset=$this->connection->query($sql);}catch(PDOException$e){throw
new
DibiDriverException($e->getMessage(),$e->getCode(),$sql);}return$this->resultset
instanceof
PDOStatement;}public
function
affectedRows(){throw
new
BadMethodCallException(__METHOD__.' is not implemented');}public
function
insertId($sequence){return$this->connection->lastInsertId();}public
function
begin(){try{$this->connection->beginTransaction();}catch(PDOException$e){throw
new
DibiDriverException($e->getMessage(),$e->getCode());}}public
function
commit(){try{$this->connection->commit();}catch(PDOException$e){throw
new
DibiDriverException($e->getMessage(),$e->getCode());}}public
function
rollback(){try{$this->connection->rollBack();}catch(PDOException$e){throw
new
DibiDriverException($e->getMessage(),$e->getCode());}}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return$this->connection->quote($value);if($type===dibi::IDENTIFIER)return$value;if($type===dibi::FIELD_BOOL)return$value?1:0;if($type===dibi::FIELD_DATE)return
date("'Y-m-d'",$value);if($type===dibi::FIELD_DATETIME)return
date("'Y-m-d H:i:s'",$value);throw
new
InvalidArgumentException('Unsupported formatting type');}public
function
applyLimit(&$sql,$limit,$offset){throw
new
BadMethodCallException(__METHOD__.' is not implemented');}public
function
rowCount(){throw
new
DibiDriverException('Row count is not available for unbuffered queries');}public
function
fetch(){return$this->resultset->fetch(PDO::FETCH_ASSOC);}public
function
seek($row){throw
new
DibiDriverException('Cannot seek an unbuffered result set');}public
function
free(){$this->resultset=NULL;}public
function
buildMeta(){$count=$this->resultset->columnCount();$meta=array();for($index=0;$index<$count;$index++){$meta=$this->resultset->getColumnMeta($index);$meta['type']=dibi::FIELD_UNKNOWN;$name=$meta['name'];$meta[$name]=$meta;}return$meta;}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiPostgreDriver
extends
NObject
implements
DibiDriverInterface{private$connection;private$resultset;public
function
connect(array&$config){DibiConnection::alias($config,'database','string');DibiConnection::alias($config,'type');if(!extension_loaded('pgsql')){throw
new
DibiException("PHP extension 'pgsql' is not loaded");}NException::catchError('DibiDriverException');if(isset($config['persistent'])){$this->connection=@pg_connect($config['database'],PGSQL_CONNECT_FORCE_NEW);}else{$this->connection=@pg_pconnect($config['database'],PGSQL_CONNECT_FORCE_NEW);}NException::restore();if(!is_resource($this->connection)){throw
new
DibiDriverException('Connecting error');}if(isset($config['charset'])){@pg_set_client_encoding($this->connection,$config['charset']);}}public
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
insertId($sequence){if($sequence===NULL){$has=$this->query("SELECT LASTVAL() AS seq");}else{$has=$this->query("SELECT CURRVAL('$sequence') AS seq");}if($has){$row=$this->fetch();$this->free();return$row['seq'];}return
FALSE;}public
function
begin(){$this->query('BEGIN');}public
function
commit(){$this->query('COMMIT');}public
function
rollback(){$this->query('ROLLBACK');}public
function
format($value,$type){if($type===dibi::FIELD_TEXT)return"'".pg_escape_string($value)."'";if($type===dibi::IDENTIFIER)return'"'.str_replace('.','"."',str_replace('"','""',$value)).'"';if($type===dibi::FIELD_BOOL)return$value?'TRUE':'FALSE';if($type===dibi::FIELD_DATE)return
date("'Y-m-d'",$value);if($type===dibi::FIELD_DATETIME)return
date("'Y-m-d H:i:s'",$value);throw
new
InvalidArgumentException('Unsupported formatting type');}public
function
applyLimit(&$sql,$limit,$offset){if($limit>=0)$sql.=' LIMIT '.(int)$limit;if($offset>0)$sql.=' OFFSET '.(int)$offset;}public
function
rowCount(){return
pg_num_rows($this->resultset);}public
function
fetch(){return
pg_fetch_array($this->resultset,NULL,PGSQL_ASSOC);}public
function
seek($row){return
pg_result_seek($this->resultset,$row);}public
function
free(){pg_free_result($this->resultset);$this->resultset=NULL;}public
function
buildMeta(){static$types=array('bool'=>dibi::FIELD_BOOL,'int2'=>dibi::FIELD_INTEGER,'int4'=>dibi::FIELD_INTEGER,'int8'=>dibi::FIELD_INTEGER,'numeric'=>dibi::FIELD_FLOAT,'float4'=>dibi::FIELD_FLOAT,'float8'=>dibi::FIELD_FLOAT,'timestamp'=>dibi::FIELD_DATETIME,'date'=>dibi::FIELD_DATE,'time'=>dibi::FIELD_DATETIME,'varchar'=>dibi::FIELD_TEXT,'bpchar'=>dibi::FIELD_TEXT,'inet'=>dibi::FIELD_TEXT,'money'=>dibi::FIELD_FLOAT,);$count=pg_num_fields($this->resultset);$meta=array();for($index=0;$index<$count;$index++){$info['native']=$native=pg_field_type($this->resultset,$index);$info['length']=pg_field_size($this->resultset,$index);$info['table']=pg_field_table($this->resultset,$index);$info['type']=isset($types[$native])?$types[$native]:dibi::FIELD_UNKNOWN;$name=pg_field_name($this->resultset,$index);$meta[$name]=$info;}return$meta;}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}class
DibiSqliteDriver
extends
NObject
implements
DibiDriverInterface{private$connection;private$resultset;private$buffered;public
function
connect(array&$config){DibiConnection::alias($config,'database','file');if(!extension_loaded('sqlite')){throw
new
DibiException("PHP extension 'sqlite' is not loaded");}$errorMsg='';if(empty($config['persistent'])){$this->connection=@sqlite_open($config['database'],0666,$errorMsg);}else{$this->connection=@sqlite_popen($config['database'],0666,$errorMsg);}if(!$this->connection){throw
new
DibiDriverException($errorMsg);}$this->buffered=empty($config['unbuffered']);}public
function
disconnect(){sqlite_close($this->connection);}public
function
query($sql){$errorMsg=NULL;if($this->buffered){$this->resultset=@sqlite_query($this->connection,$sql,SQLITE_ASSOC,$errorMsg);}else{$this->resultset=@sqlite_unbuffered_query($this->connection,$sql,SQLITE_ASSOC,$errorMsg);}if($errorMsg!==NULL){throw
new
DibiDriverException($errorMsg,sqlite_last_error($this->connection),$sql);}return
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
date("U",$value);if($type===dibi::FIELD_DATETIME)return
date("U",$value);throw
new
InvalidArgumentException('Unsupported formatting type');}public
function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.$limit.($offset>0?' OFFSET '.(int)$offset:'');}public
function
rowCount(){if(!$this->buffered){throw
new
DibiDriverException('Row count is not available for unbuffered queries');}return
sqlite_num_rows($this->resultset);}public
function
fetch(){return
sqlite_fetch_array($this->resultset,SQLITE_ASSOC);}public
function
seek($row){if(!$this->buffered){throw
new
DibiDriverException('Cannot seek an unbuffered result set');}return
sqlite_seek($this->resultset,$row);}public
function
free(){$this->resultset=NULL;}public
function
buildMeta(){$count=sqlite_num_fields($this->resultset);$meta=array();for($index=0;$index<$count;$index++){$name=sqlite_field_name($this->resultset,$index);$meta[$name]=array('type'=>dibi::FIELD_UNKNOWN);}return$meta;}public
function
getResource(){return$this->connection;}public
function
getResultResource(){return$this->resultset;}function
getDibiReflection(){}}