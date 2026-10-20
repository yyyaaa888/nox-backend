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

    'accepted' => ':attribute mesti diterima.',
    'accepted_if' => ':attribute mesti diterima apabila :other adalah :value.',
    'active_url' => ':attribute mestilah URL yang sah.',
    'after' => ':attribute mestilah tarikh selepas :date.',
    'after_or_equal' => ':attribute mestilah tarikh selepas atau sama dengan :date.',
    'alpha' => ':attribute hanya boleh mengandungi huruf.',
    'alpha_dash' => ':attribute hanya boleh mengandungi huruf, nombor, tanda tolak dan garis bawah.',
    'alpha_num' => ':attribute hanya boleh mengandungi huruf dan nombor.',
    'any_of' => ':attribute tidak sah.',
    'array' => ':attribute mestilah satu tatasusunan.',
    'ascii' => ':attribute hanya boleh mengandungi aksara alfanumerik dan simbol satu bait.',
    'before' => ':attribute mestilah tarikh sebelum :date.',
    'before_or_equal' => ':attribute mestilah tarikh sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':attribute mesti mempunyai antara :min hingga :max item.',
        'file' => ':attribute mestilah antara :min hingga :max kilobait.',
        'numeric' => ':attribute mestilah antara :min hingga :max.',
        'string' => ':attribute mestilah antara :min hingga :max aksara.',
    ],
    'boolean' => ':attribute mestilah benar atau palsu.',
    'can' => ':attribute mengandungi nilai yang tidak dibenarkan.',
    'confirmed' => 'Pengesahan :attribute tidak sepadan.',
    'contains' => ':attribute kurang nilai yang diperlukan.',
    'current_password' => 'Kata laluan tidak betul.',
    'date' => ':attribute mestilah tarikh yang sah.',
    'date_equals' => ':attribute mestilah tarikh yang sama dengan :date.',
    'date_format' => ':attribute mesti mematuhi format :format.',
    'decimal' => ':attribute mesti mempunyai :decimal tempat perpuluhan.',
    'declined' => ':attribute mesti ditolak.',
    'declined_if' => ':attribute mesti ditolak apabila :other adalah :value.',
    'different' => ':attribute dan :other mesti berbeza.',
    'digits' => ':attribute mesti terdiri daripada :digits digit.',
    'digits_between' => ':attribute mesti antara :min hingga :max digit.',
    'dimensions' => ':attribute mempunyai dimensi imej yang tidak sah.',
    'distinct' => ':attribute mempunyai nilai pendua.',
    'doesnt_contain' => ':attribute tidak boleh mengandungi mana-mana berikut: :values.',
    'doesnt_end_with' => ':attribute tidak boleh berakhir dengan mana-mana berikut: :values.',
    'doesnt_start_with' => ':attribute tidak boleh bermula dengan mana-mana berikut: :values.',
    'email' => ':attribute mestilah alamat e-mel yang sah.',
    'ends_with' => ':attribute mesti berakhir dengan salah satu daripada: :values.',
    'enum' => ':attribute yang dipilih tidak sah.',
    'exists' => ':attribute yang dipilih tidak sah.',
    'extensions' => ':attribute mesti mempunyai salah satu sambungan berikut: :values.',
    'file' => ':attribute mestilah sebuah fail.',
    'filled' => ':attribute mesti mempunyai nilai.',
    'gt' => [
        'array' => ':attribute mesti mempunyai lebih daripada :value item.',
        'file' => ':attribute mesti lebih besar daripada :value kilobait.',
        'numeric' => ':attribute mesti lebih besar daripada :value.',
        'string' => ':attribute mesti lebih panjang daripada :value aksara.',
    ],
    'gte' => [
        'array' => ':attribute mesti mempunyai :value item atau lebih.',
        'file' => ':attribute mesti lebih besar daripada atau sama dengan :value kilobait.',
        'numeric' => ':attribute mesti lebih besar daripada atau sama dengan :value.',
        'string' => ':attribute mesti lebih panjang daripada atau sama dengan :value aksara.',
    ],
    'hex_color' => ':attribute mestilah warna heksadesimal yang sah.',
    'image' => ':attribute mestilah imej.',
    'in' => ':attribute yang dipilih tidak sah.',
    'in_array' => ':attribute mesti wujud dalam :other.',
    'in_array_keys' => ':attribute mesti mengandungi sekurang-kurangnya satu kunci berikut: :values.',
    'integer' => ':attribute mestilah integer.',
    'ip' => ':attribute mestilah alamat IP yang sah.',
    'ipv4' => ':attribute mestilah alamat IPv4 yang sah.',
    'ipv6' => ':attribute mestilah alamat IPv6 yang sah.',
    'json' => ':attribute mestilah rentetan JSON yang sah.',
    'list' => ':attribute mestilah senarai.',
    'lowercase' => ':attribute mestilah huruf kecil.',
    'lt' => [
        'array' => ':attribute mesti mempunyai kurang daripada :value item.',
        'file' => ':attribute mesti kurang daripada :value kilobait.',
        'numeric' => ':attribute mesti kurang daripada :value.',
        'string' => ':attribute mesti kurang daripada :value aksara.',
    ],
    'lte' => [
        'array' => ':attribute tidak boleh mempunyai lebih daripada :value item.',
        'file' => ':attribute mesti kurang daripada atau sama dengan :value kilobait.',
        'numeric' => ':attribute mesti kurang daripada atau sama dengan :value.',
        'string' => ':attribute mesti kurang daripada atau sama dengan :value aksara.',
    ],
    'mac_address' => ':attribute mestilah alamat MAC yang sah.',
    'max' => [
        'array' => ':attribute tidak boleh mempunyai lebih daripada :max item.',
        'file' => ':attribute tidak boleh lebih besar daripada :max kilobait.',
        'numeric' => ':attribute tidak boleh lebih besar daripada :max.',
        'string' => ':attribute tidak boleh lebih panjang daripada :max aksara.',
    ],
    'max_digits' => ':attribute tidak boleh mempunyai lebih daripada :max digit.',
    'mimes' => ':attribute mestilah fail jenis: :values.',
    'mimetypes' => ':attribute mestilah fail jenis: :values.',
    'min' => [
        'array' => ':attribute mesti mempunyai sekurang-kurangnya :min item.',
        'file' => ':attribute mesti sekurang-kurangnya :min kilobait.',
        'numeric' => ':attribute mesti sekurang-kurangnya :min.',
        'string' => ':attribute mesti sekurang-kurangnya :min aksara.',
    ],
    'min_digits' => ':attribute mesti mempunyai sekurang-kurangnya :min digit.',
    'missing' => ':attribute mesti tiada.',
    'missing_if' => ':attribute mesti tiada apabila :other adalah :value.',
    'missing_unless' => ':attribute mesti tiada melainkan :other adalah :value.',
    'missing_with' => ':attribute mesti tiada apabila :values wujud.',
    'missing_with_all' => ':attribute mesti tiada apabila kesemua :values wujud.',
    'multiple_of' => ':attribute mesti gandaan bagi :value.',
    'not_in' => ':attribute yang dipilih tidak sah.',
    'not_regex' => ':attribute mempunyai format yang tidak sah.',
    'numeric' => ':attribute mestilah nombor.',
    'password' => [
        'letters' => ':attribute mesti mengandungi sekurang-kurangnya satu huruf.',
        'mixed' => ':attribute mesti mengandungi sekurang-kurangnya satu huruf besar dan satu huruf kecil.',
        'numbers' => ':attribute mesti mengandungi sekurang-kurangnya satu nombor.',
        'symbols' => ':attribute mesti mengandungi sekurang-kurangnya satu simbol.',
        'uncompromised' => ':attribute yang diberikan telah bocor dalam kebocoran data. Sila pilih :attribute yang berbeza.',
    ],
    'present' => ':attribute mesti hadir.',
    'present_if' => ':attribute mesti hadir apabila :other adalah :value.',
    'present_unless' => ':attribute mesti hadir melainkan :other adalah :value.',
    'present_with' => ':attribute mesti hadir apabila :values wujud.',
    'present_with_all' => ':attribute mesti hadir apabila kesemua :values wujud.',
    'prohibited' => ':attribute dilarang.',
    'prohibited_if' => ':attribute dilarang apabila :other adalah :value.',
    'prohibited_if_accepted' => ':attribute dilarang apabila :other diterima.',
    'prohibited_if_declined' => ':attribute dilarang apabila :other ditolak.',
    'prohibited_unless' => ':attribute dilarang melainkan :other berada dalam :values.',
    'prohibits' => ':attribute melarang :other daripada hadir.',
    'regex' => ':attribute mempunyai format yang tidak sah.',
    'required' => ':attribute adalah wajib.',
    'required_array_keys' => ':attribute mesti mengandungi entri: :values.',
    'required_if' => ':attribute adalah wajib apabila :other adalah :value.',
    'required_if_accepted' => ':attribute adalah wajib apabila :other diterima.',
    'required_if_declined' => ':attribute adalah wajib apabila :other ditolak.',
    'required_unless' => ':attribute adalah wajib melainkan :other berada dalam :values.',
    'required_with' => ':attribute adalah wajib apabila :values wujud.',
    'required_with_all' => ':attribute adalah wajib apabila kesemua :values wujud.',
    'required_without' => ':attribute adalah wajib apabila :values tidak wujud.',
    'required_without_all' => ':attribute adalah wajib apabila tiada satu pun :values wujud.',
    'same' => ':attribute mesti sepadan dengan :other.',
    'size' => [
        'array' => ':attribute mesti mengandungi :size item.',
        'file' => ':attribute mesti bersaiz :size kilobait.',
        'numeric' => ':attribute mesti bersaiz :size.',
        'string' => ':attribute mesti bersaiz :size aksara.',
    ],
    'starts_with' => ':attribute mesti bermula dengan salah satu daripada: :values.',
    'string' => ':attribute mestilah rentetan.',
    'timezone' => ':attribute mestilah zon masa yang sah.',
    'unique' => ':attribute telah digunakan.',
    'uploaded' => ':attribute gagal dimuat naik.',
    'uppercase' => ':attribute mestilah huruf besar.',
    'url' => ':attribute mestilah URL yang sah.',
    'ulid' => ':attribute mestilah ULID yang sah.',
    'uuid' => ':attribute mestilah UUID yang sah.',

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
