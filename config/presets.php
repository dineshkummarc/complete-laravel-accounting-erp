<?php

declare(strict_types=1);

return [
    'custom' => ['core', 'accounting'],
    'retail' => ['core', 'accounting', 'contacts', 'sales', 'inventory', 'pos', 'banking', 'expenses', 'tax'],
    'wholesale' => ['core', 'accounting', 'contacts', 'sales', 'inventory', 'banking', 'expenses', 'tax', 'purchases', 'collections', 'messaging', 'budgeting'],
    'manufacturing' => ['core', 'accounting', 'contacts', 'sales', 'purchases', 'inventory', 'banking', 'expenses', 'tax', 'manufacturing', 'fixed-assets', 'hr', 'attendance', 'payroll', 'budgeting'],
    'service' => ['core', 'accounting', 'contacts', 'sales', 'projects', 'expenses', 'hr', 'payroll', 'banking', 'tax'],
    'restaurant' => ['core', 'accounting', 'contacts', 'sales', 'inventory', 'pos', 'purchases', 'expenses', 'hr', 'attendance', 'payroll'],
    'freelancer' => ['core', 'accounting', 'contacts', 'sales', 'expenses', 'banking'],
];
