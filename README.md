# libs-totp

## composer.jsonへの追加

```
composer config repositories.atylab-libs vcs https://github.com/atylab-libs/libs-totp
composer require atylab-libs/totp
```

## 使用方法

```
use AtylabLibs\Totp;
```

// TOTP

```
$totp = new Totp;

// 登録用シークレット生成
$secret = $totp->convertSecret(<TOTP計算用シード値>);

// 確認用 TOTP(前後含め３ステップ生成
$totpList = $totp->createTotp(<TOTP計算用シード値>,[-1,0,1]); // 前後3件

// 入力されたTOTPを判定
$totpResult = $totp->challengeTotp(<TOTP計算用シード値>, <ユーザーが入力したTOTPの値>);
```
