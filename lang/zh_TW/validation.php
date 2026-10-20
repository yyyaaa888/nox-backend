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

    'accepted' => ':attribute 欄位必須被接受。',
    'accepted_if' => '當 :other 為 :value 時，:attribute 欄位必須被接受。',
    'active_url' => ':attribute 欄位必須是一個有效的 URL。',
    'after' => ':attribute 欄位的日期必須在 :date 之後。',
    'after_or_equal' => ':attribute 欄位的日期必須在 :date 之後或與之相等。',
    'alpha' => ':attribute 欄位只能包含字母。',
    'alpha_dash' => ':attribute 欄位只能包含字母、數字、連字元和底線。',
    'alpha_num' => ':attribute 欄位只能包含字母和數字。',
    'any_of' => ':attribute 欄位無效。',
    'array' => ':attribute 欄位必須是一個陣列。',
    'ascii' => ':attribute 欄位只能包含單位元組的字母數字字元和符號。',
    'before' => ':attribute 欄位的日期必須在 :date 之前。',
    'before_or_equal' => ':attribute 欄位的日期必須在 :date 之前或與之相等。',
    'between' => [
        'array' => ':attribute 欄位必須包含 :min 到 :max 個項目。',
        'file' => ':attribute 欄位必須在 :min 到 :max 千位元組之間。',
        'numeric' => ':attribute 欄位必須在 :min 到 :max 之間。',
        'string' => ':attribute 欄位必須在 :min 到 :max 個字元之間。',
    ],
    'boolean' => ':attribute 欄位必須為真或假。',
    'can' => ':attribute 欄位包含未授權的值。',
    'confirmed' => ':attribute 欄位的確認值不相符。',
    'contains' => ':attribute 欄位缺少必需的值。',
    'current_password' => '密碼不正確。',
    'date' => ':attribute 欄位必須是一個有效的日期。',
    'date_equals' => ':attribute 欄位的日期必須等於 :date。',
    'date_format' => ':attribute 欄位必須符合 :format 格式。',
    'decimal' => ':attribute 欄位必須有 :decimal 位小數。',
    'declined' => ':attribute 欄位必須被拒絕。',
    'declined_if' => '當 :other 為 :value 時，:attribute 欄位必須被拒絕。',
    'different' => ':attribute 欄位和 :other 必須不同。',
    'digits' => ':attribute 欄位必須是 :digits 位數字。',
    'digits_between' => ':attribute 欄位必須在 :min 到 :max 位數字之間。',
    'dimensions' => ':attribute 欄位的圖片尺寸無效。',
    'distinct' => ':attribute 欄位有重複的值。',
    'doesnt_contain' => ':attribute 欄位不能包含以下任何一項: :values。',
    'doesnt_end_with' => ':attribute 欄位不能以以下任何一項結尾: :values。',
    'doesnt_start_with' => ':attribute 欄位不能以以下任何一項開頭: :values。',
    'email' => ':attribute 欄位必須是一個有效的電子郵件地址。',
    'ends_with' => ':attribute 欄位必須以以下之一結尾: :values。',
    'enum' => '所選的 :attribute 無效。',
    'exists' => '所選的 :attribute 無效。',
    'extensions' => ':attribute 欄位必須具有以下副檔名之一: :values。',
    'file' => ':attribute 欄位必須是一個檔案。',
    'filled' => ':attribute 欄位必須有值。',
    'gt' => [
        'array' => ':attribute 欄位必須有超過 :value 個項目。',
        'file' => ':attribute 欄位必須大於 :value 千位元組。',
        'numeric' => ':attribute 欄位必須大於 :value。',
        'string' => ':attribute 欄位必須超過 :value 個字元。',
    ],
    'gte' => [
        'array' => ':attribute 欄位必須有 :value 個或更多項目。',
        'file' => ':attribute 欄位必須大於或等於 :value 千位元組。',
        'numeric' => ':attribute 欄位必須大於或等於 :value。',
        'string' => ':attribute 欄位必須大於或等於 :value 個字元。',
    ],
    'hex_color' => ':attribute 欄位必須是一個有效的十六進位顏色。',
    'image' => ':attribute 欄位必須是一張圖片。',
    'in' => '所選的 :attribute 無效。',
    'in_array' => ':attribute 欄位必須存在於 :other 中。',
    'in_array_keys' => ':attribute 欄位必須包含以下鍵中的至少一個: :values。',
    'integer' => ':attribute 欄位必須是一個整數。',
    'ip' => ':attribute 欄位必須是一個有效的 IP 位址。',
    'ipv4' => ':attribute 欄位必須是一個有效的 IPv4 位址。',
    'ipv6' => ':attribute 欄位必須是一個有效的 IPv6 位址。',
    'json' => ':attribute 欄位必須是一個有效的 JSON 字串。',
    'list' => ':attribute 欄位必須是一個清單。',
    'lowercase' => ':attribute 欄位必須為小寫。',
    'lt' => [
        'array' => ':attribute 欄位必須少於 :value 個項目。',
        'file' => ':attribute 欄位必須小於 :value 千位元組。',
        'numeric' => ':attribute 欄位必須小於 :value。',
        'string' => ':attribute 欄位必須少於 :value 個字元。',
    ],
    'lte' => [
        'array' => ':attribute 欄位不能超過 :value 個項目。',
        'file' => ':attribute 欄位必須小於或等於 :value 千位元組。',
        'numeric' => ':attribute 欄位必須小於或等於 :value。',
        'string' => ':attribute 欄位必須小於或等於 :value 個字元。',
    ],
    'mac_address' => ':attribute 欄位必須是一個有效的 MAC 位址。',
    'max' => [
        'array' => ':attribute 欄位不能超過 :max 個項目。',
        'file' => ':attribute 欄位不能大於 :max 千位元組。',
        'numeric' => ':attribute 欄位不能大於 :max。',
        'string' => ':attribute 欄位不能超過 :max 個字元。',
    ],
    'max_digits' => ':attribute 欄位不能超過 :max 位數字。',
    'mimes' => ':attribute 欄位必須是以下類型的檔案: :values。',
    'mimetypes' => ':attribute 欄位必須是以下類型的檔案: :values。',
    'min' => [
        'array' => ':attribute 欄位至少必須有 :min 個項目。',
        'file' => ':attribute 欄位至少必須為 :min 千位元組。',
        'numeric' => ':attribute 欄位至少必須為 :min。',
        'string' => ':attribute 欄位至少必須為 :min 個字元。',
    ],
    'min_digits' => ':attribute 欄位至少必須有 :min 位數字。',
    'missing' => ':attribute 欄位必須缺失。',
    'missing_if' => '當 :other 為 :value 時，:attribute 欄位必須缺失。',
    'missing_unless' => '除非 :other 為 :value，否則 :attribute 欄位必須缺失。',
    'missing_with' => '當 :values 存在時，:attribute 欄位必須缺失。',
    'missing_with_all' => '當 :values 都存在時，:attribute 欄位必須缺失。',
    'multiple_of' => ':attribute 欄位必須是 :value 的倍數。',
    'not_in' => '所選的 :attribute 無效。',
    'not_regex' => ':attribute 欄位格式無效。',
    'numeric' => ':attribute 欄位必須是一個數字。',
    'password' => [
        'letters' => ':attribute 欄位必須至少包含一個字母。',
        'mixed' => ':attribute 欄位必須至少包含一個大寫字母和一個小寫字母。',
        'numbers' => ':attribute 欄位必須至少包含一個數字。',
        'symbols' => ':attribute 欄位必須至少包含一個符號。',
        'uncompromised' => '提供的 :attribute 已在資料外洩中出現過。請換一個不同的 :attribute。',
    ],
    'present' => ':attribute 欄位必須存在。',
    'present_if' => '當 :other 為 :value 時，:attribute 欄位必須存在。',
    'present_unless' => '除非 :other 為 :value，否則 :attribute 欄位必須存在。',
    'present_with' => '當 :values 存在時，:attribute 欄位必須存在。',
    'present_with_all' => '當 :values 都存在時，:attribute 欄位必須存在。',
    'prohibited' => ':attribute 欄位被禁止。',
    'prohibited_if' => '當 :other 為 :value 時，:attribute 欄位被禁止。',
    'prohibited_if_accepted' => '當 :other 被接受時，:attribute 欄位被禁止。',
    'prohibited_if_declined' => '當 :other 被拒絕時，:attribute 欄位被禁止。',
    'prohibited_unless' => '除非 :other 在 :values 中，否則 :attribute 欄位被禁止。',
    'prohibits' => ':attribute 欄位禁止 :other 存在。',
    'regex' => ':attribute 欄位格式無效。',
    'required' => ':attribute 欄位是必需的。',
    'required_array_keys' => ':attribute 欄位必須包含以下條目: :values。',
    'required_if' => '當 :other 為 :value 時，:attribute 欄位是必需的。',
    'required_if_accepted' => '當 :other 被接受時，:attribute 欄位是必需的。',
    'required_if_declined' => '當 :other 被拒絕時，:attribute 欄位是必需的。',
    'required_unless' => '除非 :other 在 :values 中，否則 :attribute 欄位是必需的。',
    'required_with' => '當 :values 存在時，:attribute 欄位是必需的。',
    'required_with_all' => '當 :values 都存在時，:attribute 欄位是必需的。',
    'required_without' => '當 :values 不存在時，:attribute 欄位是必需的。',
    'required_without_all' => '當 :values 都不存在時，:attribute 欄位是必需的。',
    'same' => ':attribute 欄位必須與 :other 匹配。',
    'size' => [
        'array' => ':attribute 欄位必須包含 :size 個項目。',
        'file' => ':attribute 欄位必須為 :size 千位元組。',
        'numeric' => ':attribute 欄位必須為 :size。',
        'string' => ':attribute 欄位必須為 :size 個字元。',
    ],
    'starts_with' => ':attribute 欄位必須以以下之一開頭: :values。',
    'string' => ':attribute 欄位必須是一個字串。',
    'timezone' => ':attribute 欄位必須是一個有效的時區。',
    'unique' => ':attribute 已被使用。',
    'uploaded' => ':attribute 上傳失敗。',
    'uppercase' => ':attribute 欄位必須為大寫。',
    'url' => ':attribute 欄位必須是一個有效的 URL。',
    'ulid' => ':attribute 欄位必須是一個有效的 ULID。',
    'uuid' => ':attribute 欄位必須是一個有效的 UUID。',

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
