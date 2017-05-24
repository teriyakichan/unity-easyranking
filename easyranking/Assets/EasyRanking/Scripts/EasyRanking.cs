using System;
using System.Collections;
using UnityEngine;

public class EasyRanking : MonoBehaviour
{
	public string connectionPassword = "password";
	public string serverUrl = "";

	public static EasyRanking _instance;
	private static EasyRanking _getInstance()
	{
		if (_instance == null)
		{
			_instance = new GameObject().AddComponent<EasyRanking>();
			_instance.gameObject.name = "EasyRanking";
		}
		return _instance;
	}

	/// <summary>
	/// 接続設定
	/// </summary>
	/// <param name="serverUrl">接続先URL</param>
	/// <param name="connectionPassword">接続パスワード</param>
	public static void Init(string serverUrl, string connectionPassword)
	{
		EasyRanking e = _getInstance();
		e.serverUrl = serverUrl;
		e.connectionPassword = connectionPassword;
	}

	/// <summary>
	/// スコア送信
	/// </summary>
	/// <param name="name">ユーザ名</param>
	/// <param name="score">スコア</param>
	/// <param name="userCode">ユーザコード</param>
	/// <param name="callback"></param>
	public static void SendScore(string name, int score, string userCode, Action<RankingData> callback)
	{
		_getInstance().StartCoroutine(_getInstance()._sendScore(name, score, userCode, callback));
	}
	private IEnumerator _sendScore(string name, int score, string userCode, Action<RankingData> callback)
	{
		Debug.Log("send : " + name + " , " + score);
		WWWForm formData = new WWWForm();
		formData.AddField("connection_password", connectionPassword);
		formData.AddField("name", name);
		formData.AddField("score", score);
		formData.AddField("code", userCode);
		using (WWW www = new WWW(serverUrl, formData))
		{
			yield return www;
			RankingData res = JsonUtility.FromJson<RankingData>(www.text);
			callback.Invoke(res);
		}
		yield return true;
	}

	/// <summary>
	/// ランキング取得
	/// </summary>
	/// <param name="callback"></param>
	public static void GetRanking(Action<RankingData> callback)
	{
		_getInstance().StartCoroutine(_getInstance()._getRankingCoroutine(callback));
	}
	private IEnumerator _getRankingCoroutine(Action<RankingData> callback)
	{
		using (WWW www = new WWW(serverUrl))
		{
			yield return www;
			RankingData res = JsonUtility.FromJson<RankingData>(www.text);
			callback.Invoke(res);
		}
		yield return true;
	}
}

[Serializable]
public class RankingData
{
	public int status;
	public int userIndex;
	public int userRank;
	public string userCode;
	public RankingRow[] ranking;
}
[Serializable]
public class RankingRow
{
	public int rank;
	public string name;
	public float score;
}