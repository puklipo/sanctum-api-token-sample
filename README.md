# Laravel Sanctum APIトークン認証のサンプル

## バージョン
- Laravel11
- Sanctum 4.x

Laravelの話をする時は必ずバージョンを確認する。古いバージョンにはない機能や将来のバージョンでは使い方が変わってることがよくある。

## Sanctumには2つの機能がある
**APIトークン認証**と**SPA認証**。最初にこれの理解が必須。

**APIトークン認証**の使い方は簡単でSanctumの説明でよく出てくる`sanctum/csrf-cookie`とかは一切関係ない。

## プロジェクト作成
`laravel/installer`を使用。スターターキットなしを選択。
```shell
laravel new sanctum-api-token-sample
```
途中で聞かれる質問は適当に`PHPUnit` `SQLite`を選択。
DBのマイグレーションも実行。

Laravel11のapiをインストール。
```shell
cd sanctum-api-token-sample
php artisan install:api
```

```shell
   INFO  API scaffolding installed. Please add the [Laravel\Sanctum\HasApiTokens] trait to your User model.
```
`Laravel\Sanctum\HasApiTokens`を`app/Models/User.php`に追加。
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasApiTokens;
```

## テストユーザーの作成
Laravel11ならDatabaseSeederにテストユーザーを作る部分が用意されているので
```php
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
```
seedを実行するだけ。
```shell
php artisan db:seed
```

## APIトークンの作成
今回はサンプルなので`routes/console.php`にコマンドを作る。

```php
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('token:create {user=1}', function (string $user) {
    $this->info(User::findOrFail($user)->createToken(name: 'token', abilities: ['admin'])->plainTextToken);
    $this->warn('APIトークンは一度しか表示されないので必ず保存してください。');
});
```

```shell
php artisan token:create
...token...
```

`createToken()`で作成するだけなので難しいことはない。  
nameは何のためのトークンか識別するための任意の名前。ユーザーが入力してもいい。  
abilitiesは細かい権限の制御がしたい場合に使う。認証してるかの確認だけでいいなら省略可能。

plainTextTokenが実際のトークン。DB内にはハッシュ化して保存されるので元のトークンは作成直後のこの瞬間にしか存在しない。

「ブラウザで表示してユーザーに保存してもらう」とか「フロントならlocalStorageに保存する」とか
トークンをどう扱うかはSanctumを使う開発者が決めること。Laravel側は何も提示してない。ここを自分で決めないとAPIトークン認証の使い方が分からない。

## APIトークンを使って認証
APIトークン認証はHTTPリクエストのAuthorizationヘッダーに正しいトークンさえ付けていればサーバーサイドからでも別ドメインのフロントからでもモバイルアプリからでも使える。同じドメイン間のみのSPA認証との大きな違い。

### 別のLaravelプロジェクトから
```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($token)->get('https://localhost/api/user');

dump($response->json());
```

サーバーサイドからはCSRFもCORSも関係ない。  
同じLaravelプロジェクト内からはこんな使い方する必要はないので間違えないように。とんでもない初心者がやりがちなこと。

### 別ドメインのフロントエンドから
awaitが使えるかとか環境によるだろうから簡易的なサンプル。
`sanctum/csrf-cookie`なんて全く関係ないってことだけ間違えない。

```js
response = await fetch('https://localhost/api/user', {
    headers: {
        Authorization: 'Bearer ' + token
    }
});

console.log(await response.json())
```

別ドメインのフロントからリクエストが送れるかどうかはCORSで対応すること。Sanctumとは関係ない話。  
Laravel11のデフォルトでは`/api/*`以下は全部どのドメインからでも許可しているので外部向けAPIをすべて`routes/api.php`で定義するなら何もしなくていい。

## APIで外部からのログイン・ログアウト
ここはcurlを使ったサンプル。

```php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// registerも必要ならloginと同じように作る。

Route::post('login', function (Request $request) {
    if (Auth::attempt($request->only('email', 'password'))) {
        $token = Auth::user()->createToken('user')->plainTextToken;
        return response()->json(['message' => 'Login', 'token' => $token]);
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
})->middleware('guest');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::delete('logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout']);
    });
});
```

seederで作ったテストユーザー（test@example.comとpassword）でログインする。  
`php artisan serve`でLaravelを起動した状態で

```shell
curl -X POST -H "Content-Type: application/json" -d '{"email":"test@example.com","password":"password"}' http://127.0.0.1:8000/api/login
```
tokenを得られる。
```shell
{"message":"Login","token":"...token..."}
```
tokenを使ってAPIを利用したり
```shell
curl -H "Content-Type: application/json" -H "Authorization: Bearer ...token..." http://127.0.0.1:8000/api/user
```
ログアウト
```shell
curl -X DELETE -H "Content-Type: application/json" -H "Authorization: Bearer ...token..." http://127.0.0.1:8000/api/logout
{"message":"Logout"}
```

## SPA認証は全く別の機能
Sanctum内に2つの機能が含まれてるのが混乱の元なのでAPIトークン認証を使いたいならSPA認証のことは何も見ず忘れる。

SPA認証のサンプルは別で作るかもしれない。
