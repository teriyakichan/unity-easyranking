using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;

public class EasyRankingDemo : MonoBehaviour
{
	public Text textUserCode;
	public InputField inputName;
	public InputField inputScore;
	public Button buttonAddScore;
	public Button buttonGetRanking;
	public Button buttonResetUser;

	void Start()
	{
		buttonAddScore.onClick.AddListener(() => _sendScore());
		buttonGetRanking.onClick.AddListener(() => _getRanking());
		buttonResetUser.onClick.AddListener(() => _resetUser());

		// 初期設定
		EasyRanking.Init(
			"https://enter-your-domain.co.jp/ranking/index.php", // index.phpのURL
			"password" // index.phpで設定した接続用パスワード
			);
	}

	/// <summary>
	/// スコア送信
	/// </summary>
	void _sendScore()
	{
		string name = inputName.text;
		int score = int.Parse(inputScore.text);

		// スコア登録
		// name ... ユーザ名
		// score ... スコア
		// userCode ... ユーザを特定するコード
		//              SendScoreのレスポンスに含まれるので、次回以降おなじユーザコードを送信すると
		//              スコアが上書きされる(より良いスコアの場合)
		//              毎回レコードを追加したい場合はnullを指定
		EasyRanking.SendScore(name, score, PlayerPrefs.GetString("USER_CODE", null), (result) => {
			// 自分のユーザコードを取得
			string userCode = result.userCode;
			Debug.Log(userCode);
			textUserCode.text = "ユーザコード: " + (string.IsNullOrEmpty(userCode) ? "-" : userCode);
			if (!string.IsNullOrEmpty(userCode))
			{
				PlayerPrefs.SetString("USER_CODE", userCode);
				PlayerPrefs.Save();
			}

			// 自分のスコア反映後のランキング取得
			_showRanking(result);
		});
	}

	/// <summary>
	/// ランキング取得
	/// </summary>
	void _getRanking()
	{
		// ランキング取得
		EasyRanking.GetRanking((result) => {
			// 結果を出力
			_showRanking(result);
		});
	}

	/// <summary>
	/// ローカルにキャッシュしているユーザコードを削除
	/// </summary>
	void _resetUser()
	{
		PlayerPrefs.DeleteKey("USER_CODE");
		textUserCode.text = "ユーザコード: -";
	}

	/// <summary>
	/// ランキングデータをDebug.Log()出力
	/// </summary>
	/// <param name="r">ランキングデータ</param>
	void _showRanking(RankingData r)
	{
		if (r.status != 200)
		{
			Debug.LogError("ランキング取得失敗");
			return;
		}
		for (int i = 0; i < r.ranking.Length; ++i)
		{
			Debug.Log(r.ranking[i].rank + ": " + r.ranking[i].name + " - " + (int)r.ranking[i].score);
		}
	}
}
