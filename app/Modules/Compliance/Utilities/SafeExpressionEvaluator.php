<?php

namespace App\Modules\Compliance\Utilities;

use Illuminate\Support\Facades\Log;

class SafeExpressionEvaluator
{
    /**
     * Evaluate a safe expression using the Symfony ExpressionLanguage component
     * if available, otherwise fallback to a safe parser.
     *
     * @param string $expression
     * @param array $context Variables available in the expression context
     * @return bool The result of the expression evaluation
     * @throws \InvalidArgumentException If the expression contains unsafe operations
     */
    public function evaluate(string $expression, array $context = []): bool
    {
        if (empty($expression)) {
            throw new \InvalidArgumentException('Expression cannot be empty');
        }

        $expression = trim($expression);

        // Determine which evaluation strategy to use
        $evaluator = $this->createEvaluator();

        try {
            return $evaluator->evaluate($expression, $context);
        } catch (\Exception $e) {
            Log::warning('Error evaluating expression: ' . $e->getMessage(), [
                'expression' => $expression,
                'context' => $context,
            ]);
            throw new \InvalidArgumentException('Invalid expression: ' . $e->getMessage());
        }
    }

    /**
     * Create an evaluator based on available dependencies.
     */
    protected function createEvaluator(): SafeExpressionInterface
    {
        // Try to use Symfony ExpressionLanguage if available
        if (class_exists('\Symfony\Component\ExpressionLanguage\ExpressionLanguage')) {
            return new SymfonyExpressionEvaluator();
        }

        // Use our pure parser without eval()
        return new PureSafeExpressionParser();
    }
}

interface SafeExpressionInterface
{
    public function evaluate(string $expression, array $context): bool;
}

class SymfonyExpressionEvaluator implements SafeExpressionInterface
{
    /**
     * Evaluate expression using Symfony's ExpressionLanguage component.
     */
    public function evaluate(string $expression, array $context): bool
    {
        $language = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage();

        // Define safe functions for the expression language
        $safeFunctions = [
            'count' => fn ($args) => count($args[0]),
            'min' => fn ($args) => min($args),
            'max' => fn ($args) => max($args),
            'contains' => fn ($args) => in_array($args[0], $args[1]),
            'equals' => fn ($args) => $args[0] == $args[1],
            'not_equals' => fn ($args) => $args[0] != $args[1],
            'less_than' => fn ($args) => $args[0] < $args[1],
            'greater_than' => fn ($args) => $args[0] > $args[1],
            'less_equal' => fn ($args) => $args[0] <= $args[1],
            'greater_equal' => fn ($args) => $args[0] >= $args[1],
        ];

        // Restrict the variable names that can be used
        $variableScopes = ['rule'];

        foreach (array_keys($context) as $var) {
            if (!in_array($var, $variableScopes) && !preg_match('/^[a-z_][a-z0-9_]*$/i', $var)) {
                throw new \InvalidArgumentException("Unsafe variable name: {$var}");
            }
        }

        $context = array_merge($context, $safeFunctions);

        // Ensure the expression only uses a limited set of operators
        if (!$this->isSafeExpression($expression)) {
            throw new \InvalidArgumentException('Expression uses unsafe operators');
        }

        return $language->evaluate($expression, $context);
    }

    /**
     * Validate that the expression only uses safe operators and functions.
     */
    protected function isSafeExpression(string $expression): bool
    {
        // Define allowed operators
        $allowedOperators = ['==', '!=', '<', '>', '<=', '>=', 'and', 'or', 'not'];

        // Check for any disallowed characters or patterns
        if (preg_match('/eval\s*\(/i', $expression) ||
            preg_match('/system\s*\(/i', $expression) ||
            preg_match('/exec\s*\(/i', $expression) ||
            preg_match('/shell_exec\s*\(/i', $expression) ||
            preg_match('/rm\s+-rf/', $expression) ||
            preg_match('/chmod\s+/', $expression) ||
            preg_match('/chown\s+/', $expression) ||
            preg_match('/mv\s+/', $expression) ||
            preg_match('/scp\s+/', $expression) ||
            preg_match('/curl\s+/', $expression) ||
            preg_match('/wget\s+/', $expression) ||
            preg_match('/nc\s+/', $expression) ||
            preg_match('/netcat/', $expression) ||
            preg_match('/python\s+/', $expression) ||
            preg_match('/perl\s+/', $expression) ||
            preg_match('/php -r/', $expression)) {
            return false;
        }

        // Additional validation for operator and function usage
        $expression = $this->normalizeOperators($expression);

        // Allow only safe characters in the expression
        if (!preg_match('/^[\w\s()\[\].:!=<>*&|+\-~\\s;\'\"]+$/i', $expression)) {
            return false;
        }

        return true;
    }

    /**
     * Normalize operator representations in the expression.
     */
    protected function normalizeOperators(string $expression): string
    {
        // Replace various operator representations with standard ones
        return preg_replace_callback(
            '/(\s*)(and|or|not)(\s*)/i',
            function ($matches) {
                $op = strtolower($matches[2]);
                $replacement = match ($op) {
                    'and' => '&&',
                    'or' => '||',
                    'not' => '!',
                    default => $op
                };
                return $matches[1] . $replacement . $matches[3];
            },
            str_replace(['==', '!=', '<>', '<=', '>=', '<', '>'], [
                '==', '==', '==', '==', '<', '>'
            ], $expression)
        );
    }
}

/**
 * Pure safe expression parser without eval() evaluation.
 * This uses a simple token-based evaluator for boolean expressions.
 */
class PureSafeExpressionParser implements SafeExpressionInterface
{
    /**
     * Evaluate expression using our pure safe parser.
     */
    public function evaluate(string $expression, array $context): bool
    {
        $tokens = $this->tokenize($expression);
        return $this->evaluateExpression($tokens, $context);
    }

    /**
     * Tokenize the expression into a list of tokens.
     */
    protected function tokenize(string $expression): array
    {
        $expression = $this->normalizeSpaces($expression);

        $tokens = [];
        $pos = 0;
        $length = strlen($expression);

        while ($pos < $length) {
            $char = $expression[$pos];

            if (ctype_space($char)) {
                $pos++;
                continue;
            }

            if ($char === '(' || $char === ')' || $char === ',' || $char === '=' || $char === '!' || $char === '<' || $char === '>') {
                $tokens[] = $char;
                $pos++;
            } elseif ($char === '&' || $char === '|') {
                if ($pos + 1 < $length && strtolower($expression[$pos + 1]) === ($char === '&' ? 'a' : 'r')) {
                    $tokens[] = substr($expression, $pos, 2);
                    $pos += 2;
                } else {
                    $tokens[] = $char;
                    $pos++;
                }
            } elseif (preg_match('/[a-zA-Z_0-9]/', $char)) {
                $start = $pos;
                while ($pos < $length && (preg_match('/[a-zA-Z_0-9]/', $expression[$pos]) || $expression[$pos] === '.')) {
                    $pos++;
                }
                $tokens[] = substr($expression, $start, $pos - $start);
            } elseif (preg_match('/[0-9]/', $char)) {
                $start = $pos;
                while ($pos < $length && preg_match('/[0-9]/', $expression[$pos])) {
                    $pos++;
                }
                if ($pos < $length && $expression[$pos] === '.') {
                    $pos++;
                    while ($pos < $length && preg_match('/[0-9]/', $expression[$pos])) {
                        $pos++;
                    }
                }
                $tokens[] = substr($expression, $start, $pos - $start);
            } elseif (in_array($char, ["'", '"'])) {
                $quoteChar = $char;
                $start = ++$pos;
                while ($pos < $length && $expression[$pos] !== $quoteChar) {
                    $pos++;
                }
                if ($pos < $length) {
                    $tokens[] = '"' . substr($expression, $start, $pos - $start) . '"';
                    $pos++;
                } else {
                    throw new \InvalidArgumentException('Unterminated string literal');
                }
            } else {
                throw new \InvalidArgumentException("Invalid character in expression: $char");
            }
        }

        return $tokens;
    }

    /**
     * Normalize whitespace in the expression.
     */
    protected function normalizeSpaces(string $expression): string
    {
        // Convert everything to lowercase for consistency
        $expression = strtolower($expression);

        // Normalize logical operators
        $expression = preg_replace('/\s+and\s+/', ' && ', $expression);
        $expression = preg_replace('/\s+or\s+/', ' || ', $expression);
        $expression = preg_replace('/\s+not\s+/', ' ! ', $expression);

        $expression = preg_replace('/\s+/', ' ', $expression);
        $expression = trim($expression);

        return $expression;
    }

    /**
     * Evaluate a tokenized expression.
     */
    protected function evaluateExpression(array $tokens, array $context): bool
    {
        return $this->parseExpression($tokens, $context);
    }

    /**
     * Parse an expression from tokens.
     */
    protected function parseExpression(array &$tokens, array $context): bool
    {
        $result = $this->parseLogicalOr($tokens, $context);

        if (!empty($tokens)) {
            throw new \InvalidArgumentException('Unexpected tokens at end of expression');
        }

        return $result;
    }

    protected function parseLogicalOr(array &$tokens, array $context): bool
    {
        $result = $this->parseLogicalAnd($tokens, $context);

        while (!empty($tokens) && $tokens[0] === '||') {
            array_shift($tokens);
            $right = $this->parseLogicalAnd($tokens, $context);
            $result = $result || $right;
        }

        return $result;
    }

    protected function parseLogicalAnd(array &$tokens, array $context): bool
    {
        $result = $this->parseComparison($tokens, $context);

        while (!empty($tokens) && $tokens[0] === '&&') {
            array_shift($tokens);
            $right = $this->parseComparison($tokens, $context);
            $result = $result && $right;
        }

        return $result;
    }

    protected function parseComparison(array &$tokens, array $context): bool
    {
        $left = $this->parseTerm($tokens, $context);

        if (!empty($tokens) && in_array($tokens[0], ['==', '!=', '<', '>', '<=', '>='])) {
            $operator = array_shift($tokens);
            $right = $this->parseTerm($tokens, $context);

            return $this->evaluateComparison($left, $operator, $right);
        }

        return $left;
    }

    protected function parseTerm(array &$tokens, array $context): mixed
    {
        if (empty($tokens)) {
            throw new \InvalidArgumentException('Unexpected end of expression');
        }

        $token = array_shift($tokens);

        if ($token === '(') {
            $result = $this->parseExpression($tokens, $context);
            if (empty($tokens) || $tokens[0] !== ')') {
                throw new \InvalidArgumentException('Expected closing parenthesis');
            }
            array_shift($tokens);
            return $result;
        }

        return $this->parseLiteral($token, $context);
    }

    /**
     * Parse a literal value.
     */
    protected function parseLiteral(string $token, array $context): mixed
    {
        if ($token === 'true') {
            return true;
        }
        if ($token === 'false') {
            return false;
        }
        if ($token === 'null') {
            return null;
        }

        if (is_numeric($token)) {
            return $token + 0;
        }

        if (str_starts_with($token, '"') && str_ends_with($token, '"')) {
            return substr($token, 1, -1);
        }

        if (isset($context[$token])) {
            return $context[$token];
        }

        throw new \InvalidArgumentException("Unknown variable or literal: $token");
    }

    /**
     * Evaluate a comparison operation.
     */
    protected function evaluateComparison(mixed $left, string $operator, mixed $right): bool
    {
        switch ($operator) {
            case '==':
                return $left == $right;
            case '!=':
                return $left != $right;
            case '<':
                return $left < $right;
            case '>':
                return $left > $right;
            case '<=':
                return $left <= $right;
            case '>=':
                return $left >= $right;
            default:
                throw new \InvalidArgumentException("Unsupported operator: $operator");
        }
    }
}