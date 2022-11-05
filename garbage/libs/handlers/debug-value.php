<?php
/*
 * Generate debug value
 */
load_libs('synonyms');

if (!Debug::enabled()) {
    return '';
}

switch ($format) {
    case 'username':
        // no-break
    case 'word':
        return synonym_random(1, true);

    case 'name':
        return not_empty(Strings::force(synonym_random(not_empty($size, mt_rand(1, 4))), ' '), str_random(not_empty($size, 32), false, '0123456789abcdefghijklmnopqrstuvwxyz     '));

    case 'text':
        // no-break
    case 'words':
        return not_empty(Strings::force(synonym_random(not_empty($size, mt_rand(5, 15))), ' '), str_random(not_empty($size, 150), false, '0123456789abcdefghijklmnopqrstuvwxyz     '));

    case 'email':
        return str_replace('-', '', str_replace(' ', '', not_empty(Strings::force(synonym_random(mt_rand(1, 2), true), str_random(mt_rand(0, 1), false, '._-')), str_random())).'@'.str_replace(' ', '', not_empty(Strings::force(synonym_random(mt_rand(1, 2), true), str_random(mt_rand(0, 1), false, '_-')), str_random()).'.com'));

    case 'url':
        return str_replace(' ', '', 'http://'.not_empty(Strings::force(synonym_random(mt_rand(1, 2), true), str_random(mt_rand(0, 1), false, '._-')), str_random()).'.'.pick_random(1, 'com', 'co', 'mx', 'org', 'net', 'guru'));

    case 'random':
        return str_random(not_empty($size, 150), false, '0123456789abcdefghijklmnopqrstuvwxyz     ');

    case 'zip':
        // no-break
    case 'zipcode':
        return str_random(not_empty($size, 5), false, '0123456789');

    case 'number':
        return str_random(not_empty($size, 8), false, '0123456789');

    case 'address':
        return str_random().' '.str_random(not_empty($size, 8), false, '0123456789');

    case 'password':
        return 'aaaaaaaa';

    case 'money':
        if (!$size) {
            $size = 5000;
        }

        return mt_rand(1, $size) / 100;

    case 'checked':
        if ($size) {
            return ' checked ';
        }

        return '';

    default:
        return $format;
}
?>