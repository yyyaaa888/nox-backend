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

    'accepted' => ':attribute(은)는 승인되어야 합니다.',
    'accepted_if' => ':other이(가) :value일 때, :attribute(은)는 승인되어야 합니다.',
    'active_url' => ':attribute(은)는 유효한 URL이어야 합니다.',
    'after' => ':attribute(은)는 :date 이후의 날짜여야 합니다.',
    'after_or_equal' => ':attribute(은)는 :date 이후 또는 같은 날짜여야 합니다.',
    'alpha' => ':attribute(은)는 문자만 포함할 수 있습니다.',
    'alpha_dash' => ':attribute(은)는 문자, 숫자, 대시, 밑줄만 포함할 수 있습니다.',
    'alpha_num' => ':attribute(은)는 문자와 숫자만 포함할 수 있습니다.',
    'any_of' => ':attribute(이)가 유효하지 않습니다.',
    'array' => ':attribute(은)는 배열이어야 합니다.',
    'ascii' => ':attribute(은)는 영숫자 및 기호(단일 바이트)만 포함할 수 있습니다.',
    'before' => ':attribute(은)는 :date 이전의 날짜여야 합니다.',
    'before_or_equal' => ':attribute(은)는 :date 이전 또는 같은 날짜여야 합니다.',
    'between' => [
        'array' => ':attribute(은)는 :min에서 :max개의 항목을 포함해야 합니다.',
        'file' => ':attribute(은)는 :min에서 :max킬로바이트 사이여야 합니다.',
        'numeric' => ':attribute(은)는 :min에서 :max 사이여야 합니다.',
        'string' => ':attribute(은)는 :min에서 :max자 사이여야 합니다.',
    ],
    'boolean' => ':attribute(은)는 참 또는 거짓이어야 합니다.',
    'can' => ':attribute(에)에 승인되지 않은 값이 포함되어 있습니다.',
    'confirmed' => ':attribute 확인 값이 일치하지 않습니다.',
    'contains' => ':attribute(에) 필요한 값이 포함되어 있지 않습니다.',
    'current_password' => '비밀번호가 올바르지 않습니다.',
    'date' => ':attribute(은)는 유효한 날짜여야 합니다.',
    'date_equals' => ':attribute(은)는 :date와 같은 날짜여야 합니다.',
    'date_format' => ':attribute(은)는 :format 형식과 일치해야 합니다.',
    'decimal' => ':attribute(은)는 :decimal자리 소수여야 합니다.',
    'declined' => ':attribute(은)는 거부되어야 합니다.',
    'declined_if' => ':other이(가) :value일 때, :attribute(은)는 거부되어야 합니다.',
    'different' => ':attribute(과)와 :other(이)가 달라야 합니다.',
    'digits' => ':attribute(은)는 :digits자리 숫자여야 합니다.',
    'digits_between' => ':attribute(은)는 :min에서 :max자리 숫자여야 합니다.',
    'dimensions' => ':attribute(의) 이미지 크기가 잘못되었습니다.',
    'distinct' => ':attribute(에) 중복된 값이 있습니다.',
    'doesnt_contain' => ':attribute(은)는 다음을 포함할 수 없습니다: :values.',
    'doesnt_end_with' => ':attribute(은)는 다음으로 끝날 수 없습니다: :values.',
    'doesnt_start_with' => ':attribute(은)는 다음으로 시작할 수 없습니다: :values.',
    'email' => ':attribute(은)는 유효한 이메일 주소여야 합니다.',
    'ends_with' => ':attribute(은)는 다음 중 하나로 끝나야 합니다: :values.',
    'enum' => '선택한 :attribute(이)가 유효하지 않습니다.',
    'exists' => '선택한 :attribute(이)가 유효하지 않습니다.',
    'extensions' => ':attribute(은)는 다음 확장자 중 하나여야 합니다: :values.',
    'file' => ':attribute(은)는 파일이어야 합니다.',
    'filled' => ':attribute(에) 값이 있어야 합니다.',
    'gt' => [
        'array' => ':attribute(은)는 :value개보다 많은 항목을 포함해야 합니다.',
        'file' => ':attribute(은)는 :value킬로바이트보다 커야 합니다.',
        'numeric' => ':attribute(은)는 :value보다 커야 합니다.',
        'string' => ':attribute(은)는 :value자보다 많아야 합니다.',
    ],
    'gte' => [
        'array' => ':attribute(은)는 :value개 이상의 항목을 포함해야 합니다.',
        'file' => ':attribute(은)는 :value킬로바이트 이상이어야 합니다.',
        'numeric' => ':attribute(은)는 :value 이상이어야 합니다.',
        'string' => ':attribute(은)는 :value자 이상이어야 합니다.',
    ],
    'hex_color' => ':attribute(은)는 유효한 16진수 색상이어야 합니다.',
    'image' => ':attribute(은)는 이미지 파일이어야 합니다.',
    'in' => '선택한 :attribute(이)가 유효하지 않습니다.',
    'in_array' => ':attribute(은)는 :other에 존재해야 합니다.',
    'in_array_keys' => ':attribute(은)는 다음 키 중 하나를 포함해야 합니다: :values.',
    'integer' => ':attribute(은)는 정수여야 합니다.',
    'ip' => ':attribute(은)는 유효한 IP 주소여야 합니다.',
    'ipv4' => ':attribute(은)는 유효한 IPv4 주소여야 합니다.',
    'ipv6' => ':attribute(은)는 유효한 IPv6 주소여야 합니다.',
    'json' => ':attribute(은)는 유효한 JSON 문자열이어야 합니다.',
    'list' => ':attribute(은)는 목록이어야 합니다.',
    'lowercase' => ':attribute(은)는 소문자여야 합니다.',
    'lt' => [
        'array' => ':attribute(은)는 :value개보다 적은 항목을 포함해야 합니다.',
        'file' => ':attribute(은)는 :value킬로바이트보다 작아야 합니다.',
        'numeric' => ':attribute(은)는 :value보다 작아야 합니다.',
        'string' => ':attribute(은)는 :value자보다 적어야 합니다.',
    ],
    'lte' => [
        'array' => ':attribute(은)는 :value개 이하의 항목을 포함해야 합니다.',
        'file' => ':attribute(은)는 :value킬로바이트 이하여야 합니다.',
        'numeric' => ':attribute(은)는 :value 이하여야 합니다.',
        'string' => ':attribute(은)는 :value자 이하여야 합니다.',
    ],
    'mac_address' => ':attribute(은)는 유효한 MAC 주소여야 합니다.',
    'max' => [
        'array' => ':attribute(은)는 :max개를 초과할 수 없습니다.',
        'file' => ':attribute(은)는 :max킬로바이트를 초과할 수 없습니다.',
        'numeric' => ':attribute(은)는 :max를 초과할 수 없습니다.',
        'string' => ':attribute(은)는 :max자를 초과할 수 없습니다.',
    ],
    'max_digits' => ':attribute(은)는 :max자리를 초과할 수 없습니다.',
    'mimes' => ':attribute(은)는 다음 유형의 파일이어야 합니다: :values.',
    'mimetypes' => ':attribute(은)는 다음 MIME 유형이어야 합니다: :values.',
    'min' => [
        'array' => ':attribute(은)는 최소 :min개의 항목을 포함해야 합니다.',
        'file' => ':attribute(은)는 최소 :min킬로바이트여야 합니다.',
        'numeric' => ':attribute(은)는 최소 :min이어야 합니다.',
        'string' => ':attribute(은)는 최소 :min자여야 합니다.',
    ],
    'min_digits' => ':attribute(은)는 최소 :min자리 숫자여야 합니다.',
    'missing' => ':attribute(은)는 존재하면 안 됩니다.',
    'missing_if' => ':other이(가) :value일 때, :attribute(은)는 존재하면 안 됩니다.',
    'missing_unless' => ':other이(가) :value가 아니면, :attribute(은)는 존재하면 안 됩니다.',
    'missing_with' => ':values(이)가 존재할 때, :attribute(은)는 존재하면 안 됩니다.',
    'missing_with_all' => ':values(이)가 모두 존재할 때, :attribute(은)는 존재하면 안 됩니다.',
    'multiple_of' => ':attribute(은)는 :value의 배수여야 합니다.',
    'not_in' => '선택한 :attribute(이)가 유효하지 않습니다.',
    'not_regex' => ':attribute 형식이 유효하지 않습니다.',
    'numeric' => ':attribute(은)는 숫자여야 합니다.',
    'password' => [
        'letters' => ':attribute(은)는 최소 하나의 문자를 포함해야 합니다.',
        'mixed' => ':attribute(은)는 최소 하나의 대문자와 소문자를 포함해야 합니다.',
        'numbers' => ':attribute(은)는 최소 하나의 숫자를 포함해야 합니다.',
        'symbols' => ':attribute(은)는 최소 하나의 기호를 포함해야 합니다.',
        'uncompromised' => '제공된 :attribute(이)가 데이터 유출에 노출된 적이 있습니다. 다른 :attribute를 사용하세요.',
    ],
    'present' => ':attribute(은)는 존재해야 합니다.',
    'present_if' => ':other이(가) :value일 때, :attribute(은)는 존재해야 합니다.',
    'present_unless' => ':other이(가) :value가 아니면, :attribute(은)는 존재해야 합니다.',
    'present_with' => ':values(이)가 존재할 때, :attribute(은)는 존재해야 합니다.',
    'present_with_all' => ':values(이)가 모두 존재할 때, :attribute(은)는 존재해야 합니다.',
    'prohibited' => ':attribute(은)는 금지되어 있습니다.',
    'prohibited_if' => ':other이(가) :value일 때, :attribute(은)는 금지되어 있습니다.',
    'prohibited_if_accepted' => ':other(이)가 승인되면, :attribute(은)는 금지되어 있습니다.',
    'prohibited_if_declined' => ':other(이)가 거부되면, :attribute(은)는 금지되어 있습니다.',
    'prohibited_unless' => ':other(이)가 :values에 포함되지 않으면, :attribute(은)는 금지되어 있습니다.',
    'prohibits' => ':attribute(은)는 :other의 존재를 금지합니다.',
    'regex' => ':attribute 형식이 유효하지 않습니다.',
    'required' => ':attribute(은)는 필수입니다.',
    'required_array_keys' => ':attribute(은)는 다음 항목을 포함해야 합니다: :values.',
    'required_if' => ':other이(가) :value일 때, :attribute(은)는 필수입니다.',
    'required_if_accepted' => ':other(이)가 승인되면, :attribute(은)는 필수입니다.',
    'required_if_declined' => ':other(이)가 거부되면, :attribute(은)는 필수입니다.',
    'required_unless' => ':other(이)가 :values에 포함되지 않으면, :attribute(은)는 필수입니다.',
    'required_with' => ':values(이)가 존재할 때, :attribute(은)는 필수입니다.',
    'required_with_all' => ':values(이)가 모두 존재할 때, :attribute(은)는 필수입니다.',
    'required_without' => ':values(이)가 존재하지 않을 때, :attribute(은)는 필수입니다.',
    'required_without_all' => ':values(이)가 모두 존재하지 않을 때, :attribute(은)는 필수입니다.',
    'same' => ':attribute(은)는 :other와 일치해야 합니다.',
    'size' => [
        'array' => ':attribute(은)는 :size개의 항목을 포함해야 합니다.',
        'file' => ':attribute(은)는 :size킬로바이트여야 합니다.',
        'numeric' => ':attribute(은)는 :size이어야 합니다.',
        'string' => ':attribute(은)는 :size자여야 합니다.',
    ],
    'starts_with' => ':attribute(은)는 다음 중 하나로 시작해야 합니다: :values.',
    'string' => ':attribute(은)는 문자열이어야 합니다.',
    'timezone' => ':attribute(은)는 유효한 타임존이어야 합니다.',
    'unique' => ':attribute(이)가 이미 사용 중입니다.',
    'uploaded' => ':attribute(을)를 업로드하지 못했습니다.',
    'uppercase' => ':attribute(은)는 대문자여야 합니다.',
    'url' => ':attribute(은)는 유효한 URL이어야 합니다.',
    'ulid' => ':attribute(은)는 유효한 ULID여야 합니다.',
    'uuid' => ':attribute(은)는 유효한 UUID여야 합니다.',

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
