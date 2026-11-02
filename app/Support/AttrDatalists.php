<?php

namespace App\Support;

use App\Services\ConObjs;
use App\Services\RefDocs;

/**
 * The lookup lists behind the attribute forms (units ~600, reference documents,
 * construction objects) are the same for every field on a page, so each is printed once
 * per page as a shared <datalist> and every field references it by id. Printing one per
 * field made the draft editor of a large template ~20 MB of HTML.
 */
class AttrDatalists
{
    public const REFDOCS = 'pe-dl-refdocs';
    public const CONOBJS = 'pe-dl-conobjs';

    public static function enumId(string $field): string
    {
        return 'pe-dl-enum-' . $field;
    }

    /** The <datalist> markup the first time $id is asked for on this request, else ''. */
    public static function once(string $id, \Closure $optionsHtml): string
    {
        $printed = request()->attributes->get('pe_datalists', []);
        if (isset($printed[$id])) {
            return '';
        }
        $printed[$id] = true;
        request()->attributes->set('pe_datalists', $printed);

        return '<datalist id="' . e($id) . '">' . $optionsHtml() . '</datalist>';
    }

    public static function lookup(bool $refdocs): string
    {
        return self::once($refdocs ? self::REFDOCS : self::CONOBJS, function () use ($refdocs) {
            $html = '';
            foreach ($refdocs ? RefDocs::all() : ConObjs::all() as $o) {
                $html .= '<option data-guid="' . e($o->GUID) . '" value="' . e($o->label) . '"></option>';
            }

            return $html;
        });
    }

    public static function enum(string $field, array $values): string
    {
        return self::once(self::enumId($field), function () use ($values) {
            $html = '';
            foreach ($values as $v) {
                $html .= '<option value="' . e($v) . '"></option>';
            }

            return $html;
        });
    }
}
