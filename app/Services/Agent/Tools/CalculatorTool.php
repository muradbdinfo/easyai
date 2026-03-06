<?php

namespace App\Services\Agent\Tools;

class CalculatorTool extends BaseTool
{
    public function name(): string
    {
        return 'calculator';
    }

    public function description(): string
    {
        return 'Evaluate mathematical expressions. Use for arithmetic, percentages, currency conversions, or any calculation. Do NOT guess at math — always use this tool.';
    }

    public function parameters(): array
    {
        return [
            [
                'name'        => 'expression',
                'type'        => 'string',
                'description' => 'A mathematical expression to evaluate, e.g. "25 * 4 + 100", "sqrt(144)", "(500 * 1.15) / 12"',
                'required'    => true,
            ],
        ];
    }

    public function execute(array $input): string
    {
        $expression = trim($input['expression'] ?? '');

        if (!$expression) {
            return 'Error: expression is required.';
        }

        // Sanitize: only allow safe math characters
        $cleaned = preg_replace('/[^0-9\+\-\*\/\.\(\)\%\s]/', '', $expression);

        // Replace common function names before cleaning
        $withFunctions = $this->replaceMathFunctions($expression);
        $cleaned       = preg_replace('/[^0-9\+\-\*\/\.\(\)\s]/', '', $withFunctions);

        if (!trim($cleaned)) {
            return "Error: expression contains unsupported characters: {$expression}";
        }

        try {
            $result = $this->safeEval($cleaned);

            if ($result === null) {
                return "Error: could not evaluate \"{$expression}\"";
            }

            // Format result
            if (is_float($result)) {
                // Round to reasonable precision
                $formatted = round($result, 8);
                // Remove trailing zeros
                $formatted = rtrim(rtrim(number_format($formatted, 8, '.', ''), '0'), '.');
            } else {
                $formatted = (string) $result;
            }

            return "Result: {$expression} = {$formatted}";

        } catch (\Throwable $e) {
            return "Calculation error: {$e->getMessage()}";
        }
    }

    private function replaceMathFunctions(string $expr): string
    {
        // Replace common math functions with their numeric results via preg callbacks
        // This handles sqrt, pow, abs, round, ceil, floor, log, pi
        $expr = preg_replace_callback('/sqrt\(([0-9.\s\+\-\*\/\(\)]+)\)/i', function($m) {
            return (string) sqrt((float) $m[1]);
        }, $expr);

        $expr = preg_replace_callback('/abs\(([0-9.\s\+\-\*\/\(\)]+)\)/i', function($m) {
            return (string) abs((float) $m[1]);
        }, $expr);

        $expr = preg_replace_callback('/round\(([0-9.\s\+\-\*\/\(\)]+)\)/i', function($m) {
            return (string) round((float) $m[1]);
        }, $expr);

        $expr = preg_replace_callback('/ceil\(([0-9.\s\+\-\*\/\(\)]+)\)/i', function($m) {
            return (string) ceil((float) $m[1]);
        }, $expr);

        $expr = preg_replace_callback('/floor\(([0-9.\s\+\-\*\/\(\)]+)\)/i', function($m) {
            return (string) floor((float) $m[1]);
        }, $expr);

        $expr = preg_replace_callback('/pow\(([0-9.]+)\s*,\s*([0-9.]+)\)/i', function($m) {
            return (string) pow((float) $m[1], (float) $m[2]);
        }, $expr);

        // pi() → 3.14159...
        $expr = str_ireplace(['pi()', 'PI'], (string) M_PI, $expr);

        // Handle percentage: "50%" → "0.5"
        $expr = preg_replace('/([0-9.]+)%/', '($1/100)', $expr);

        // Handle "^" as power operator
        $expr = preg_replace('/([0-9.]+)\^([0-9.]+)/', 'pow($1,$2)', $expr);

        return $expr;
    }

    private function safeEval(string $expression): float|int|null
    {
        // Final whitelist check — only digits, operators, parens, dots, spaces
        if (!preg_match('/^[0-9\+\-\*\/\.\(\)\s]+$/', $expression)) {
            return null;
        }

        // Guard division by zero
        if (preg_match('/\/\s*0(?![0-9])/', $expression)) {
            throw new \RuntimeException('Division by zero');
        }

        // Use bc-style evaluation via create_function replacement
        // Safe because we've whitelisted chars above
        $result = null;

        // Convert to postfix and evaluate using stack
        // Simple approach: use PHP tokenizer on whitelisted expression
        try {
            // phpcs:ignore
            $result = eval("return ({$expression});");
        } catch (\ParseError $e) {
            return null;
        }

        return is_numeric($result) ? $result : null;
    }
}