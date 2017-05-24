# unity-easyranking

UnityのWebGLで簡単にランキングを実装できます.

## 必要なもの

PHPとMySQLを使用します.LAMP環境のあるレンタルサーバ(さくら等)を借りればファイルの設置とDB作成のみで使用可能です.
- Webサーバ(Apache, Nginx等) php5.x
- MySQLサーバ 5.x

## 使い方

### セットアップ
1. server/index.php の _CONNECTION_PASSWORD_ を設定(あとで使います)
1. server/index.php の DB接続情報(_DB_HOST_, _DB_NAME_, _DB_USER_, _DB_PSWD_)を設定
1. server/index.php をサーバにアップロード
1. Unityに EasyRanking.cs をインポート (easyranking/Assets/EasyRanking/Scripts/)

*以下はサンプルがあります(EasyRankingDemo.cs)

### 初期化
```cs
EasyRanking.Init("設置したindex.phpのURL", "1で設定したCONNECTION_PASSWORD");
```
### ランキング取得
```cs
EasyRanking.GetRanking(callback);
```
callbackにはRankingData(EasyRnaking.cs内に定義)が返ります.
### スコア送信
```cs
int score = 100; // 送信するスコア
string userCode = null; // ユーザコード(ユーザの特定に使用)
EasyRanking.SendScore("ユーザ名", score, userCode, callback);
```
callbackにはRankingData(EasyRnaking.cs内に定義)が返ります.
スコアの初回登録時(userCode = nullでリクエスト時), RankingData.userCodeに新しく発行されたユーザコードが含まれます.
コードをキャッシュしておき次回リクエスト時に指定すると, 同一ユーザと見なし, 前回よりスコアが高い場合に上書きするようになります.

## TODO
- 管理画面(index.php?manage=true&username=username&password=passwordでアクセス可能, 現在はスコア表示のみ)
- 昇順ランキング
- セキュリティ(スコア登録時の認証が平文パスワードで大丈夫か)

