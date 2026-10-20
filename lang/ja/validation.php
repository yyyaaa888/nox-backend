<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute は承認されなければなりません。',
    'accepted_if' => ':other が :value の場合、:attribute は承認されなければなりません。',
    'active_url' => ':attribute は有効なURLでなければなりません。',
    'after' => ':attribute は :date より後の日付でなければなりません。',
    'after_or_equal' => ':attribute は :date 以降の日付でなければなりません。',
    'alpha' => ':attribute は文字のみを含めることができます。',
    'alpha_dash' => ':attribute は文字、数字、ダッシュ、アンダースコアのみを含めることができます。',
    'alpha_num' => ':attribute は文字と数字のみを含めることができます。',
    'any_of' => ':attribute は無効です。',
    'array' => ':attribute は配列でなければなりません。',
    'ascii' => ':attribute は英数字と記号のみ（単一バイト）を含めることができます。',
    'before' => ':attribute は :date より前の日付でなければなりません。',
    'before_or_equal' => ':attribute は :date 以前の日付でなければなりません。',
    'between' => [
        'array' => ':attribute は :min 〜 :max 個の項目を含む必要があります。',
        'file' => ':attribute は :min 〜 :max キロバイトの間でなければなりません。',
        'numeric' => ':attribute は :min 〜 :max の間でなければなりません。',
        'string' => ':attribute は :min 〜 :max 文字の間でなければなりません。',
    ],
    'boolean' => ':attribute は真または偽でなければなりません。',
    'can' => ':attribute に許可されていない値が含まれています。',
    'confirmed' => ':attribute の確認入力が一致しません。',
    'contains' => ':attribute に必要な値が含まれていません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attribute は有効な日付でなければなりません。',
    'date_equals' => ':attribute は :date と同じ日付でなければなりません。',
    'date_format' => ':attribute は :format 形式と一致していなければなりません。',
    'decimal' => ':attribute は :decimal 桁の小数である必要があります。',
    'declined' => ':attribute は拒否されなければなりません。',
    'declined_if' => ':other が :value の場合、:attribute は拒否されなければなりません。',
    'different' => ':attribute と :other は異なる値でなければなりません。',
    'digits' => ':attribute は :digits 桁の数字でなければなりません。',
    'digits_between' => ':attribute は :min 〜 :max 桁の数字でなければなりません。',
    'dimensions' => ':attribute の画像サイズが無効です。',
    'distinct' => ':attribute に重複した値があります。',
    'doesnt_contain' => ':attribute は以下のいずれも含んではいけません: :values。',
    'doesnt_end_with' => ':attribute は以下のいずれでも終わってはいけません: :values。',
    'doesnt_start_with' => ':attribute は以下のいずれでも始まってはいけません: :values。',
    'email' => ':attribute は有効なメールアドレスでなければなりません。',
    'ends_with' => ':attribute は以下のいずれかで終わっていなければなりません: :values。',
    'enum' => '選択された :attribute は無効です。',
    'exists' => '選択された :attribute は無効です。',
    'extensions' => ':attribute は以下の拡張子のいずれかでなければなりません: :values。',
    'file' => ':attribute はファイルでなければなりません。',
    'filled' => ':attribute には値を指定する必要があります。',
    'gt' => [
        'array' => ':attribute は :value 個より多くの項目を含む必要があります。',
        'file' => ':attribute は :value キロバイトより大きくなければなりません。',
        'numeric' => ':attribute は :value より大きくなければなりません。',
        'string' => ':attribute は :value 文字より多くなければなりません。',
    ],
    'gte' => [
        'array' => ':attribute は :value 個以上の項目を含む必要があります。',
        'file' => ':attribute は :value キロバイト以上でなければなりません。',
        'numeric' => ':attribute は :value 以上でなければなりません。',
        'string' => ':attribute は :value 文字以上でなければなりません。',
    ],
    'hex_color' => ':attribute は有効な16進数カラーコードでなければなりません。',
    'image' => ':attribute は画像ファイルでなければなりません。',
    'in' => '選択された :attribute は無効です。',
    'in_array' => ':attribute は :other に存在しなければなりません。',
    'in_array_keys' => ':attribute は以下のいずれかのキーを含む必要があります: :values。',
    'integer' => ':attribute は整数でなければなりません。',
    'ip' => ':attribute は有効なIPアドレスでなければなりません。',
    'ipv4' => ':attribute は有効なIPv4アドレスでなければなりません。',
    'ipv6' => ':attribute は有効なIPv6アドレスでなければなりません。',
    'json' => ':attribute は有効なJSON文字列でなければなりません。',
    'list' => ':attribute はリストでなければなりません。',
    'lowercase' => ':attribute は小文字でなければなりません。',
    'lt' => [
        'array' => ':attribute は :value 個より少ない項目を含む必要があります。',
        'file' => ':attribute は :value キロバイトより小さくなければなりません。',
        'numeric' => ':attribute は :value より小さくなければなりません。',
        'string' => ':attribute は :value 文字より少なければなりません。',
    ],
    'lte' => [
        'array' => ':attribute は :value 個以下の項目を含む必要があります。',
        'file' => ':attribute は :value キロバイト以下でなければなりません。',
        'numeric' => ':attribute は :value 以下でなければなりません。',
        'string' => ':attribute は :value 文字以下でなければなりません。',
    ],
    'mac_address' => ':attribute は有効なMACアドレスでなければなりません。',
    'max' => [
        'array' => ':attribute は :max 個を超える項目を含んではいけません。',
        'file' => ':attribute は :max キロバイトを超えてはいけません。',
        'numeric' => ':attribute は :max を超えてはいけません。',
        'string' => ':attribute は :max 文字を超えてはいけません。',
    ],
    'max_digits' => ':attribute は :max 桁を超えてはいけません。',
    'mimes' => ':attribute は以下のファイルタイプのいずれかでなければなりません: :values。',
    'mimetypes' => ':attribute は以下のMIMEタイプのいずれかでなければなりません: :values。',
    'min' => [
        'array' => ':attribute は少なくとも :min 個の項目を含む必要があります。',
        'file' => ':attribute は少なくとも :min キロバイトでなければなりません。',
        'numeric' => ':attribute は少なくとも :min でなければなりません。',
        'string' => ':attribute は少なくとも :min 文字でなければなりません。',
    ],
    'min_digits' => ':attribute は少なくとも :min 桁の数字でなければなりません。',
    'missing' => ':attribute は存在してはいけません。',
    'missing_if' => ':other が :value の場合、:attribute は存在してはいけません。',
    'missing_unless' => ':other が :value でない限り、:attribute は存在してはいけません。',
    'missing_with' => ':values が存在する場合、:attribute は存在してはいけません。',
    'missing_with_all' => ':values がすべて存在する場合、:attribute は存在してはいけません。',
    'multiple_of' => ':attribute は :value の倍数でなければなりません。',
    'not_in' => '選択された :attribute は無効です。',
    'not_regex' => ':attribute の形式が無効です。',
    'numeric' => ':attribute は数値でなければなりません。',
    'password' => [
        'letters' => ':attribute には少なくとも1つの文字を含める必要があります。',
        'mixed' => ':attribute には少なくとも1つの大文字と1つの小文字を含める必要があります。',
        'numbers' => ':attribute には少なくとも1つの数字を含める必要があります。',
        'symbols' => ':attribute には少なくとも1つの記号を含める必要があります。',
        'uncompromised' => '提供された :attribute はデータ漏洩で流出したことがあります。別の :attribute を使用してください。',
    ],
    'present' => ':attribute は存在しなければなりません。',
    'present_if' => ':other が :value の場合、:attribute は存在しなければなりません。',
    'present_unless' => ':other が :value でない限り、:attribute は存在しなければなりません。',
    'present_with' => ':values が存在する場合、:attribute は存在しなければなりません。',
    'present_with_all' => ':values がすべて存在する場合、:attribute は存在しなければなりません。',
    'prohibited' => ':attribute は禁止されています。',
    'prohibited_if' => ':other が :value の場合、:attribute は禁止されています。',
    'prohibited_if_accepted' => ':other が承認された場合、:attribute は禁止されています。',
    'prohibited_if_declined' => ':other が拒否された場合、:attribute は禁止されています。',
    'prohibited_unless' => ':other が :values に含まれない限り、:attribute は禁止されています。',
    'prohibits' => ':attribute は :other の存在を禁止しています。',
    'regex' => ':attribute の形式が無効です。',
    'required' => ':attribute は必須です。',
    'required_array_keys' => ':attribute は以下のキーを含む必要があります: :values。',
    'required_if' => ':other が :value の場合、:attribute は必須です。',
    'required_if_accepted' => ':other が承認された場合、:attribute は必須です。',
    'required_if_declined' => ':other が拒否された場合、:attribute は必須です。',
    'required_unless' => ':other が :values に含まれない限り、:attribute は必須です。',
    'required_with' => ':values が存在する場合、:attribute は必須です。',
    'required_with_all' => ':values がすべて存在する場合、:attribute は必須です。',
    'required_without' => ':values が存在しない場合、:attribute は必須です。',
    'required_without_all' => ':values がすべて存在しない場合、:attribute は必須です。',
    'same' => ':attribute は :other と一致していなければなりません。',
    'size' => [
        'array' => ':attribute は :size 個の項目を含む必要があります。',
        'file' => ':attribute は :size キロバイトでなければなりません。',
        'numeric' => ':attribute は :size でなければなりません。',
        'string' => ':attribute は :size 文字でなければなりません。',
    ],
    'starts_with' => ':attribute は以下のいずれかで始まらなければなりません: :values。',
    'string' => ':attribute は文字列でなければなりません。',
    'timezone' => ':attribute は有効なタイムゾーンでなければなりません。',
    'unique' => ':attribute は既に使用されています。',
    'uploaded' => ':attribute のアップロードに失敗しました。',
    'uppercase' => ':attribute は大文字でなければなりません。',
    'url' => ':attribute は有効なURLでなければなりません。',
    'ulid' => ':attribute は有効なULIDでなければなりません。',
    'uuid' => ':attribute は有効なUUIDでなければなりません。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
