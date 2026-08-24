<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // メールアドレスとパスワードの入力チェック
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // ユーザーの存在確認
        $user = User::where('email', $request->email)->first();

        // パスワードの照合（不一致なら401エラー）
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'ログイン情報が正しくありません。',
            ], 401);
        }

        // SanctumでAPIトークンを発行
        $token = $user->createToken('auth_token')->plainTextToken;

        // クライアント（Postmanやアプリ）にトークンを返す
        return response()->json([
            'message' => 'ログイン成功',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * 2. ログアウト処理（トークン破棄）
     */
    public function logout(Request $request)
    {
        // 現在のリクエストで使われているトークンを削除する
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'ログアウトしました',
        ]);
    }
}
