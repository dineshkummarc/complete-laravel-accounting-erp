<?php

declare(strict_types=1);

return [
    ['code' => '1100', 'name' => 'Cash', 'name_bn' => 'নগদ', 'type' => 'asset', 'normal_balance' => 'debit'],
    ['code' => '1110', 'name' => 'Bank', 'name_bn' => 'ব্যাংক', 'type' => 'asset', 'normal_balance' => 'debit'],
    ['code' => '1120', 'name' => 'Mobile wallets', 'name_bn' => 'মোবাইল ওয়ালেট', 'type' => 'asset', 'normal_balance' => 'debit'],
    ['code' => '1200', 'name' => 'Accounts receivable', 'name_bn' => 'প্রাপ্য হিসাব', 'type' => 'asset', 'normal_balance' => 'debit'],
    ['code' => '1300', 'name' => 'Inventory', 'name_bn' => 'মজুত', 'type' => 'asset', 'normal_balance' => 'debit'],
    ['code' => '1320', 'name' => 'Input tax', 'name_bn' => 'উপকরণ কর', 'type' => 'asset', 'normal_balance' => 'debit'],
    ['code' => '1350', 'name' => 'Work in progress', 'name_bn' => 'চলমান উৎপাদন', 'type' => 'asset', 'normal_balance' => 'debit'],
    ['code' => '1400', 'name' => 'Fixed assets', 'name_bn' => 'স্থায়ী সম্পদ', 'type' => 'asset', 'normal_balance' => 'debit'],
    ['code' => '1490', 'name' => 'Accumulated depreciation', 'name_bn' => 'পুঞ্জীভূত অবচয়', 'type' => 'asset', 'normal_balance' => 'credit'],
    ['code' => '1500', 'name' => 'Inter-branch receivable', 'name_bn' => 'আন্তঃশাখা প্রাপ্য', 'type' => 'asset', 'normal_balance' => 'debit'],
    ['code' => '2100', 'name' => 'Accounts payable', 'name_bn' => 'প্রদেয় হিসাব', 'type' => 'liability', 'normal_balance' => 'credit'],
    ['code' => '2200', 'name' => 'Output tax', 'name_bn' => 'বিক্রয় কর', 'type' => 'liability', 'normal_balance' => 'credit'],
    ['code' => '2300', 'name' => 'Payroll payable', 'name_bn' => 'বেতন প্রদেয়', 'type' => 'liability', 'normal_balance' => 'credit'],
    ['code' => '2310', 'name' => 'Provident fund payable', 'name_bn' => 'ভবিষ্যৎ তহবিল প্রদেয়', 'type' => 'liability', 'normal_balance' => 'credit'],
    ['code' => '2400', 'name' => 'Inter-branch payable', 'name_bn' => 'আন্তঃশাখা প্রদেয়', 'type' => 'liability', 'normal_balance' => 'credit'],
    ['code' => '2500', 'name' => 'Goods received not invoiced', 'name_bn' => 'চালানবিহীন প্রাপ্ত পণ্য', 'type' => 'liability', 'normal_balance' => 'credit'],
    ['code' => '3100', 'name' => 'Retained earnings', 'name_bn' => 'অবণ্টিত মুনাফা', 'type' => 'equity', 'normal_balance' => 'credit'],
    ['code' => '3200', 'name' => 'Opening balance equity', 'name_bn' => 'প্রারম্ভিক মূলধন', 'type' => 'equity', 'normal_balance' => 'credit'],
    ['code' => '4100', 'name' => 'Sales revenue', 'name_bn' => 'বিক্রয় আয়', 'type' => 'income', 'normal_balance' => 'credit'],
    ['code' => '4110', 'name' => 'Sales returns', 'name_bn' => 'বিক্রয় ফেরত', 'type' => 'income', 'normal_balance' => 'debit'],
    ['code' => '4200', 'name' => 'Foreign exchange gains', 'name_bn' => 'বৈদেশিক মুদ্রা লাভ', 'type' => 'income', 'normal_balance' => 'credit'],
    ['code' => '5100', 'name' => 'Cost of goods sold', 'name_bn' => 'বিক্রীত পণ্যের ব্যয়', 'type' => 'expense', 'normal_balance' => 'debit'],
    ['code' => '5200', 'name' => 'Operating expenses', 'name_bn' => 'পরিচালন ব্যয়', 'type' => 'expense', 'normal_balance' => 'debit'],
    ['code' => '5300', 'name' => 'Payroll expense', 'name_bn' => 'বেতন ব্যয়', 'type' => 'expense', 'normal_balance' => 'debit'],
    ['code' => '5400', 'name' => 'Depreciation expense', 'name_bn' => 'অবচয় ব্যয়', 'type' => 'expense', 'normal_balance' => 'debit'],
    ['code' => '5500', 'name' => 'Foreign exchange losses', 'name_bn' => 'বৈদেশিক মুদ্রা ক্ষতি', 'type' => 'expense', 'normal_balance' => 'debit'],
    ['code' => '5600', 'name' => 'Rounding adjustments', 'name_bn' => 'রাউন্ডিং সমন্বয়', 'type' => 'expense', 'normal_balance' => 'debit'],
];
