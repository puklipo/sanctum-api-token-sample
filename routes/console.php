<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\PersonalAccessToken;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('token:create {user=1}', function (string $user) {
    $this->info(User::findOrFail($user)->createToken(name: 'token', abilities: ['admin'])->plainTextToken);
    $this->warn('APIトークンは一度しか表示されないので必ず保存してください。');
});

Artisan::command('token:user {token}', function (string $token) {
    $accessToken = PersonalAccessToken::findToken($token);
    $user = $accessToken->tokenable->withAccessToken($accessToken);
    $this->info($user->name);
    $this->info($user->tokenCan('admin'));
})->purpose('トークンからユーザーを取得。サンプルでの確認用なので通常はこんな使い方はしない。');
