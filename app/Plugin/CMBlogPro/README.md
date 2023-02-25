# ECCube4 - ブログプラグイン
```
    PHP 7.1 〜 7.4
    Database
    PostgreSQL 9.2.x / 10.x (pg_settingsテーブルへの参照権限 必須)
    MySQL 5.5.x / 5.6.x / 5.7.x (InnoDBエンジン 必須)
```

## フォルダー構成
```
├── Controller              # コントローラ
│   ├── Admin
│   ├── Web
├── Entity
├── Form                    # FormBuilder用
│   ├── Type
│   │   ├── Admin
├── Repository
├── Resource                # HTML,CSS,JS用
│   ├── assets
│   │   ├── js
│   ├── locale
│   ├── template
│   │   ├── Admin           # 管理画面のHTML用
│   │   │   ├── blog
│   │   │   ├── category
│   │   ├── web             # 一般公開のHTML用
├── Tests                   # テスト
│   ├── Admin               # 管理画面に対するテスト
│   ├── Web                 # 一般公開に対するテスト
```

## ECCube4プラグイン用の開発ドキュメント
* [ECCube4プラグイン開発](https://doc4.ec-cube.net/plugin_spec)
* [EC-CUBE4系プラグイン「残り在庫数表示プラグイン」](https://qiita.com/yoshiharu-semachi/items/03817d6dd883b000348f)

## テスト
phpunit.xml.distのファイルはEC-CubeのRootフォルダに入れて下さい
