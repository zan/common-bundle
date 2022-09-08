<?php


namespace Zan\CommonBundle\Util;


use Symfony\Component\HttpFoundation\Request;

class RequestUtils
{
    /**
     * Returns the parameters in the query string of $request as an array
     *
     * See getParametersFromQueryString() for more details on why this exists
     *
     * @return array<string>
     */
    public static function getParameters(Request $request): array
    {
        // Doesn't work in sf5
        //$queryString = $request->getQueryString();
        $queryString = $_SERVER['QUERY_STRING'];

        return static::getParametersFromQueryString($queryString);
    }

    /**
     * Parses parameters from a query string and adds support for cgi-style arrays (represented by repeated names)
     *
     * For example, ?myVal=one&myVal=two should parse as myVal being an array with two elements ['one', 'two']
     *
     * By default, PHP parses myVal as a string with value 'two'
     *
     * @return array<string>
     */
    public static function getParametersFromQueryString(string $queryString): array
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

        // Find duplicate parameter names that need to be converted to name[] syntax
        $toFix = static::getToFix($queryString);

        // Loop through the query string and insert correct array syntax so parse_str can be used below
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
                    $foundParamNames[] = $paramName;
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

    /**
     * Looks through $queryString and extracts all parameter names
     *
     * @return array<string>
     */
    protected static function getToFix(string $queryString): array
    {
        $seenNames = [];
        $toFix = [];

        $buffer = [];
        $chars = str_split($queryString);
        $state = 'LOOKING_EQUALS';
        foreach ($chars as $char) {
            $currChar = $char;
            if ('LOOKING_EQUALS' == $state) {
                // Found an =, this means end of the parameter name
                if ($currChar == '=') {
                    $paramName = join('', $buffer);

                    if (array_key_exists($paramName, $seenNames)) {
                        $toFix[] = $paramName;
                    }

                    $state = 'LOOKING_AMPERSAND';
                    $seenNames[$paramName] = true;
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
                    $buffer = [];
                    continue;
                }
            }
        }

        return $toFix;
    }
}