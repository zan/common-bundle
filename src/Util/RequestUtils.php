<?php


namespace Zan\CommonBundle\Util;


use Symfony\Component\HttpFoundation\Request;

class RequestUtils
{
    /**
     * todo: needs tests and docs
     */
    public static function getParameters(Request $request)
    {
        // Special case: PHP does not support standard CGI array syntax:
        //      http://example.com?fields=one&fields=two&fields=three
        //      Should parse as 'fields' having a value of ['one', 'two', 'three']
        //      Instead, you end up with 'fields' having a value of 'three' (the last element in the array)
        //
        // To work around this, detect duplicates in the array and replace them with
        // PHP array syntax
        //
        //      http://example.com?fields=one&fields=two&fields=three
        //      becomes
        //      http://example.com?fields[]=one&fields[]=two&fields[]=three

        // Doesn't work in sf5
        //$queryString = $request->getQueryString();
        $queryString = $_SERVER['QUERY_STRING'];

        // First, parse as normal
        $defaultParsing = [];
        parse_str($queryString, $defaultParsing);

        $toFix = [];
        foreach ($defaultParsing as $key => $value) {
            if (substr_count($queryString, $key) > 1) {
                $toFix[] = $key;
            }
        }

        $buffer = [];
        $chars = str_split($queryString);
        $finStr = '';
        $state = 'LOOKING_EQUALS';
        foreach ($chars as $char) {
            $currChar = $char;
            if ('LOOKING_EQUALS' == $state) {
                // Found an =, this means end of the parameter name
                if ($currChar == '=') {
                    $paramName = join('', $buffer);
                    // Parameter is a duplicated one, append '[]'
                    if (in_array($paramName, $toFix)) {
                        $paramName .= '[]';
                    }
                    $finStr .= $paramName . '=';
                    $buffer = [];
                    $state = 'LOOKING_AMPERSAND';
                    continue;
                }
                else {
                    $buffer[] = $char;
                }
            }
            if ('LOOKING_AMPERSAND' == $state) {
                $buffer[] = $char;
                if ($currChar == '&') {
                    $state = 'LOOKING_EQUALS';
                    $finStr .= join('', $buffer);
                    $buffer = [];
                    continue;
                }
            }
        }
        // Append anything left in the buffer
        $finStr .= join('', $buffer);

        $fixedParams = [];
        parse_str($finStr, $fixedParams);

        return $fixedParams;
    }
}