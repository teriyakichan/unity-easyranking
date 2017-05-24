<?php
/* config */
// 接続パスワード(Unity)
define('CONNECTION_PASSWORD', 'Unityで設定したランキング接続パスワード');

// MySQL接続情報
define('DB_HOST', 'MySQLサーバのアドレス');
define('DB_NAME', 'データベースの名前');
define('DB_USER', 'データベースのユーザ名');
define('DB_PSWD', 'データベースのパスワード');
// 管理画面ユーザ名/パスワード
define('MANAGER_USERNAME', 'admin');
define('MANAGER_PASSWORD', 'password');


$ranking = null;

if (isset($_GET['manage']) && $_GET['manage'] == 'true') {
  if (!isset($_GET['username']) || $_GET['username'] != MANAGER_USERNAME) return showError('Invalid username.');
  if (!isset($_GET['password']) || $_GET['password'] != MANAGER_PASSWORD) return showError('Invalid password.');
  // 管理画面
  $ranking = getRanking(null, null, true);
} else {
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
  switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      $userCode = isset($_GET['code']) ? $_GET['code'] : null;
      echo json_encode(getRanking($userCode));
      return;
    case 'POST':
      addScore();
      return;
    default:
      return;
  }
}

/**
 * 汎用エラー表示
 */
function showError($msg) { echo $msg; }

/**
 * スコア登録＆ユーザ作成(いなければ)
 */
function addScore() {
  if (!isset($_POST['connection_password']) || $_POST['connection_password'] != CONNECTION_PASSWORD) {
    echo json_encode(array('status' => 401));
    return;
  }

  if (!isset($_POST['score'])) { return; }
  if (!isset($_POST['name'])) { return; }
  $link = connect();
  $userCode = isset($_POST['code']) ? $_POST['code'] : null;
  $oldScore = 0;

  // check record already exists
  if ($userCode !== null) {
    $query = 'SELECT code, score FROM scores WHERE code = "' . $_POST['code'] . '";';
    $result = select($query);
    if (count($result) != 0) {
      $userCode = $result[0]['code'];
      $oldScore = $result[0]['score'];
    } else {
      $userCode = null;
    }
  }

  // upsert
  $now = date('Y-m-d H:i:s');
  if ($userCode === null) {
    $userCode = sha1(uniqid(mt_rand(), true));
    $query = 'INSERT INTO scores (name, code, score, modified, created) VALUES ("' . $_POST['name'] . '", "' . $userCode . '", ' . $_POST['score'] . ', "' . $now . '", "' . $now . '");';
    mysql_query($query);
  } else {
    if ($oldScore < $_POST['score']) {
      $query = 'UPDATE scores SET name = "' . $_POST['name'] . '", score = ' . $_POST['score'] . ', modified = "' . $now . '" WHERE code = "' . $userCode . '";';
      mysql_query($query);
    }
  }

  echo json_encode(getRanking($userCode, $link));
  disconnect($link);
}

/**
 * ランキング取得
 */
function getRanking($userCode, $link = null, $fullData = false) {
  $connectRequired = $link == null;
  if ($connectRequired) $link = connect();

  $fields = $fullData ? '*' : 'name, code, score';
  $query = 'SELECT ' . $fields . ' FROM scores WHERE 1 ORDER BY score DESC;';
  $result = mysql_query($query);
  if (!$result) initTable();

  $ranking = [];
  $userRank = 0;
  $userIndex = -1;
  $lastScore = null;
  $rank = 0;
  $index = 0;
  while ($row = mysql_fetch_assoc($result)) {
    $rank += $lastScore == $row['score'] ? 0 : 1;
    $lastScore = $row['score'];
    if ($fullData) {
      $ranking[] = $row;
      $ranking[count($ranking) - 1]['rank'] = $rank;
    } else {
      $ranking[] = array(
        'rank' => $rank,
        'name' => $row['name'],
        'score' => $row['score'],
        );
    }
    if ($row['code'] == $userCode) {
      $userRank = $rank;
      $userIndex = $index;
      $userCode = $row['code'];
    }
    ++$index;
  }

  if ($connectRquired) disconnect($link);
  return array(
    'status' => 200,
    'userIndex' => $userIndex,
    'userRank' => $userRank,
    'userCode' => $userCode,
    'ranking' => $ranking,
    );
}

/**
 * テーブル作成
 */
function initTable() {
  $query =
    'CREATE TABLE `scores` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `name` varchar(64) NOT NULL DEFAULT \'\',
      `code` varchar(128) NOT NULL DEFAULT \'\',
      `score` double NOT NULL,
      `modified` datetime NOT NULL,
      `created` datetime NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
  mysql_query($query);
}


// mysql functions
function connect() {
  $link = mysql_connect(DB_HOST, DB_USER, DB_PSWD);
  if (!$link) die('connection error : ' . mysql_error());
  $db_selected = mysql_select_db(DB_NAME, $link);
  if (!$db_selected) die('select db error : ' . mysql_error());
  mysql_set_charset('utf8');
  return $link;
}
function disconnect($link) {
  $close_flag = mysql_close($link);
}
function select($query) {
  $result = mysql_query($query);
  if (!$result) initTable();
  $array = [];
  while ($row = mysql_fetch_assoc($result)) $array[] = $row;
  return $array;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8"/>
</head>
<body>
<div><h1>unity-easyranking manager</h1></div>
<div>登録スコア数: <?php echo count($ranking['ranking']); ?></div>
<?php if (count($ranking['ranking']) > 0) { ?>
<table><tbody>
<tr><th>id</th><th>name</th><th>code</th><th>score</th><th>modified</th><th>created</th><th>del</th></tr>
<?php foreach ($ranking['ranking'] as $row) { ?>
<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['code']; ?></td>
<td><?php echo $row['score']; ?></td>
<td><?php echo $row['modified']; ?></td>
<td><?php echo $row['created']; ?></td>
<td><a href="#" onClick="deleteScoreDialog(<?php echo $row['id']; ?>);">[x]</a></td>
</tr>  
<?php } ?>
</tbody></table>
<?php } ?>
</body>
</html>
