<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'email' => 'يجب أن يكون :attribute بريدًا إلكترونيًا صحيحًا.',
    'unique' => 'قيمة :attribute مستخدمة من قبل.',
    'exists' => 'القيمة المحددة في :attribute غير صالحة.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'current_password' => 'كلمة المرور الحالية غير صحيحة.',
    'min' => ['string' => 'يجب ألا يقل :attribute عن :min أحرف.', 'numeric' => 'يجب ألا يقل :attribute عن :min.'],
    'max' => ['string' => 'يجب ألا يزيد :attribute عن :max حرفًا.', 'numeric' => 'يجب ألا يزيد :attribute عن :max.'],
    'date' => 'يجب أن يكون :attribute تاريخًا صحيحًا.',
    'after' => 'يجب أن يكون :attribute بعد :date.',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'between' => ['numeric' => 'يجب أن تكون قيمة :attribute بين :min و:max.'],
    'alpha_dash' => 'يقبل :attribute الحروف والأرقام والشرطات فقط.',
    'password' => [
        'letters' => 'يجب أن تحتوي كلمة المرور على حرف واحد على الأقل.',
        'mixed' => 'يجب أن تحتوي كلمة المرور على حرف كبير وآخر صغير.',
        'numbers' => 'يجب أن تحتوي كلمة المرور على رقم واحد على الأقل.',
        'symbols' => 'يجب أن تحتوي كلمة المرور على رمز خاص واحد على الأقل.',
    ],
];
