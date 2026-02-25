<?php

namespace Tests\Unit;

use App\Http\Requests\SubmitAnswerRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class AnswerValidationTest extends TestCase
{
    // ── Answer format regex ──────────────────────────────────────────────────

    /**
     *
     * @dataProvider validAnswerFormatProvider
     */
    public function test_valid_answer_formats_match_regex(string $input): void
    {
        $this->assertMatchesRegularExpression(
            '/^-?\d+$/',
            $input,
            "Expected '$input' to be accepted as a valid answer format."
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validAnswerFormatProvider(): array
    {
        return [
            'positive integer' => ['42'],
            'zero' => ['0'],
            'negative integer' => ['-7'],
            'large positive integer' => ['1000'],
            'large negative integer' => ['-999'],
        ];
    }

    /**
     * @dataProvider invalidAnswerFormatProvider
     */
    public function test_invalid_answer_formats_do_not_match_regex(string $input): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/^-?\d+$/',
            $input,
            "Expected '$input' to be rejected as an invalid answer format."
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidAnswerFormatProvider(): array
    {
        return [
            'decimal number' => ['3.14'],
            'alphabetic string' => ['abc'],
            'alphanumeric string' => ['12abc'],
            'scientific notation' => ['1e5'],
            'empty string' => [''],
            'whitespace only' => ['   '],
            'two numbers with space' => ['1 2'],
            'leading plus sign' => ['+5'],
        ];
    }

    // ── getValidatedAnswer() casting ─────────────────────────────────────────

    public function test_get_validated_answer_trims_whitespace_and_returns_int(): void
    {
        $request = $this->makeRequest(' 42 ');

        $this->assertSame(42, $request->getValidatedAnswer());
    }

    public function test_get_validated_answer_preserves_negative_integers(): void
    {
        $request = $this->makeRequest('-7');

        $this->assertSame(-7, $request->getValidatedAnswer());
    }

    public function test_get_validated_answer_handles_zero(): void
    {
        $request = $this->makeRequest('0');

        $this->assertSame(0, $request->getValidatedAnswer());
    }

    public function test_get_validated_answer_always_returns_int_type(): void
    {
        $request = $this->makeRequest('100');

        $this->assertIsInt($request->getValidatedAnswer());
    }

    /**
     * Build a SubmitAnswerRequest with the given raw answer value in the
     * POST body, bypassing HTTP routing so we can test the method in
     * isolation without booting the full framework.
     */
    private function makeRequest(string $answerValue): SubmitAnswerRequest
    {
        $symfony = Request::create(
            uri: '/',
            method: 'POST',
            parameters: ['answer' => $answerValue],
        );

        return SubmitAnswerRequest::createFromBase($symfony);
    }
}
