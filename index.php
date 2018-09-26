<?php
  // CHANGE THIS!
  $username = "shortener";
  $password = "letmein";
  // IP WHITELIST FOR ADMIN FUNCTIONALITY - USE ENTRY '*' for ALL
  $whitelist = array('192.168.200.*','127.0.0.1');

  header("X-Content-Type-Options: nosniff");
  header("X-Frame-Options: DENY");
  header("X-XSS-Protection: 1; mode=block");
  header("Content-Security-Policy: default-src 'none';");
  if(usingHTTPS()){
    header("Strict-Transport-Security: max-age=2592000");
  }

  session_start();
  if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
  }
  $token = $_SESSION['token'];
  $db = loadDB();

  // ADDING URL
  if(isset($_POST["url"])){
    checkCSRF();
    checkLogin($whitelist);
    $res = addEntry($db,$_POST["url"]);
    if($res == false){
      die("This is not a valid URL");
    }else{
      header("Location: ?admin&highlight=".$res);
      die();
    }
  // LOGOUT
  }elseif(isset($_POST["logout"])){
    checkLogin($whitelist);
    unset($_SESSION["loggedin"]);
    die();
  // LOGIN
  }elseif(isset($_POST["usr"])&&isset($_POST["pwd"])){
    checkCSRF();
    $usrok = hash_equals($_POST["usr"],$username);
    $pwdok = hash_equals($_POST["pwd"],$password);
    $loginok = $usrok && $pwdok;
    if($loginok){
      $_SESSION["loggedin"]=true;
      header("Location: ?admin");
      die();
    }else{
      die("Login failed");
    }
  // DELETE URL
  }elseif(isset($_POST["delete"])){
    checkCSRF();
    checkLogin($whitelist);
    deleteEntry($db,$_POST["delete"]);
    header("Location: ?admin");
    die();
  // ADMIN "DASHBOARD"
  }elseif(isset($_GET["admin"])){
    checkLogin($whitelist);
    ?>
      <br>
      <form method="POST">
        URL to shorten: <input type="text" name="url"><br>
        <input type="hidden" name="csrf" value="<?= $token ?>">
        <input type="submit" value="Create Short URL">
      </form>
      <hr><br>
      <form method="POST">
        <input type="hidden" name="logout" value="true">
        <input type="hidden" name="csrf" value="<?= $token ?>">
        <input type="submit" value="Logout">
      </form>
      <hr><br>
    <?php
    foreach ($db as $key=>$entry) {
      if(isset($_GET["highlight"]) && $key===$_GET["highlight"]){
        echo("<span style='color:green'><b>".$key." - ".$entry.'</b></span> <a href="?'.$key.'">Short URL</a>');
      }else{
        echo($key." - ".$entry.' <a href="?'.$key.'">Short URL</a>');
      }
      ?>
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this shortened URL?\n\rThis might break existing links.');">
          <input type="hidden" name="delete" value="<?= $key ?>">
          <input type="hidden" name="csrf" value="<?= $token ?>">
          <input type="submit" value="Delete">
        </form>
      <?php
    }
    die();
  // RESOLVE SHORT CODE IF EXISTS
  }else{
    parse_str($_SERVER['QUERY_STRING'], $getparams);
    $code=key($getparams);
    if($code!=""){
      $url = resolveURL($db,$code);
      if($url !== false){
        header("Location: ".$url);
        die();
      }
    }
  }
  die();

  function checkCSRF(){
    if(!isset($_POST["csrf"]) || !hash_equals($_POST["csrf"],$_SESSION["token"])){
      die("CSRF failed");
    }
  }

  function checkLogin($whitelist){
    if(!$_SESSION["loggedin"]){
      if(!isAllowed($_SERVER['REMOTE_ADDR'],$whitelist)){
        die("I don't know you");
      }
      ?>
        <form method="POST">
          Username: <input type="text" name="usr"><br>
          Password: <input type="password" name="pwd"><br>
          <input type="hidden" name="csrf" value="<?= $_SESSION["token"] ?>">
          <input type="submit" value="Login">
        </form>
      <?php
      die();
    }
  }

  function resolveURL($db,$code){
    if(isset($db[$code])){
      return $db[$code];
    }
    return false;
  }

  function addEntry(&$db,$url){
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
      return false;
    }
    $code=getShortCode($url);
    $db[$code] = $url;
    saveDB($db);
    return $code;
  }

  function deleteEntry(&$db,$code){
    if(isset($db[$code])){
      unset($db[$code]);
      saveDB($db);
    }
  }

  function getShortCode($db){
    do{
      $code = generateRandomString();
    } while (array_key_exists($code,$db));
    return $code;
  }

  function generateRandomString($length = 5) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
  }

  function saveDB($db){
    $dbfp = "db.json";
    $json_data = json_encode($db);
    $res = file_put_contents($dbfp, $json_data);
    if(!$res){
      die("Error saving DB");
    }
  }

  function loadDB(){
    $dbfp = "db.json";
    $dbf = fopen($dbfp, "r");
    if($dbf == null){
      createDB($dbfp);
      $dbf = fopen($dbfp, "r");
    }
    $json = json_decode(fread($dbf,filesize($dbfp)),true);
    if(json_last_error()!==JSON_ERROR_NONE){
      die("Error reading DB (".json_last_error().")");
    }
    fclose($dbf);
    return $json;
  }

  function createDB($dbfp){
    $dbf = fopen($dbfp, 'w') or die('Cannot create db');
    fwrite($dbf, "{}");
    fclose($db);
  }


  function isAllowed($ip,$whitelist){
    if(in_array($ip, $whitelist)){
      return true;
    }else{
      foreach($whitelist as $i){
        $wildcardPos = strpos($i, "*");
        if($wildcardPos !== false && substr($_SERVER['REMOTE_ADDR'], 0, $wildcardPos) . "*" == $i){
          return true;
        }
      }
    }
    return false;
  }

  function usingHTTPS(){
    return ( (! empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') ||
    (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (! empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') );
  }
?>
